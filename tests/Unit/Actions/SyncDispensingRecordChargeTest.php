<?php

declare(strict_types=1);

use App\Actions\SyncDispensingRecordCharge;
use App\Enums\DispensingRecordStatus;
use App\Enums\PrescriptionItemStatus;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItem;
use App\Models\VisitCharge;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require_once __DIR__.'/../../Feature/Controllers/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('creates one visit charge per dispensed record item', function (): void {
    [$branch, , $user, $staff, $pharmacyLocation, , , $readyDrug] = createPharmacyModuleContext();

    $readyDrug->forceFill([
        'default_selling_price' => 1500,
    ])->save();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [[
            'inventory_item_id' => $readyDrug->id,
            'quantity' => 4,
        ]],
    );

    DB::table('visit_payers')->insert([
        'id' => (string) Str::uuid(),
        'tenant_id' => $branch->tenant_id,
        'patient_visit_id' => $prescription->visit_id,
        'billing_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $record = DispensingRecord::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'visit_id' => $prescription->visit_id,
        'prescription_id' => $prescription->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'dispense_number' => 'DSP-LINE-001',
        'dispensed_by' => $user->id,
        'dispensed_at' => now(),
        'status' => DispensingRecordStatus::POSTED,
    ]);

    $recordItem = DispensingRecordItem::query()->create([
        'dispensing_record_id' => $record->id,
        'prescription_item_id' => $prescription->items->first()->id,
        'inventory_item_id' => $readyDrug->id,
        'prescribed_quantity' => 4,
        'dispensed_quantity' => 3,
        'balance_quantity' => 1,
        'dispense_status' => PrescriptionItemStatus::PARTIAL,
        'external_pharmacy' => false,
    ]);

    resolve(SyncDispensingRecordCharge::class)->handle($record);

    $charge = VisitCharge::query()
        ->where('patient_visit_id', $prescription->visit_id)
        ->where('source_type', $recordItem->getMorphClass())
        ->where('source_id', $recordItem->id)
        ->first();

    expect($charge)->not()->toBeNull()
        ->and((float) $charge->unit_price)->toBe(1500.0)
        ->and((float) $charge->quantity)->toBe(3.0)
        ->and((float) $charge->line_total)->toBe(4500.0);
});
