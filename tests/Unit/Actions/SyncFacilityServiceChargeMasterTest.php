<?php

declare(strict_types=1);

use App\Actions\CreateFacilityService;
use App\Actions\SyncFacilityServiceOrderCharge;
use App\Enums\FacilityServiceCategory;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\PayerType;
use App\Models\ChargeMaster;
use App\Models\FacilityBranch;
use App\Models\FacilityServiceOrder;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

it('creates a charge master when a billable facility service is created', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    $service = resolve(CreateFacilityService::class)->handle([
        'tenant_id' => $user->tenant_id,
        'name' => 'Ultrasound',
        'category' => FacilityServiceCategory::OTHER,
        'description' => 'Ultrasound imaging service',
        'cost_price' => 40,
        'unit_price' => 90,
        'is_billable' => true,
        'is_active' => true,
    ]);

    $chargeMaster = ChargeMaster::query()->find($service->charge_master_id);

    expect($service->charge_master_id)->not()->toBeNull()
        ->and($chargeMaster)->not()->toBeNull()
        ->and($chargeMaster?->item_code)->toBe($service->service_code)
        ->and($chargeMaster?->billable_id)->toBe($service->id)
        ->and($chargeMaster?->unit_price)->toBe('90.00');
});

it('links synced facility service visit charges to the governed charge master', function (): void {
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $staff = Staff::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    actingAs($user);

    $patientId = (string) Str::uuid();
    $visitId = (string) Str::uuid();
    $payerId = (string) Str::uuid();
    $billingId = (string) Str::uuid();

    DB::table('patients')->insert([
        'id' => $patientId,
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-301',
        'first_name' => 'Charge',
        'last_name' => 'Link',
        'gender' => 'male',
        'phone_number' => '+256701111111',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('patient_visits')->insert([
        'id' => $visitId,
        'tenant_id' => $tenant->id,
        'patient_id' => $patientId,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-301',
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'is_emergency' => false,
        'registered_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_payers')->insert([
        'id' => $payerId,
        'tenant_id' => $tenant->id,
        'patient_visit_id' => $visitId,
        'billing_type' => PayerType::CASH->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('visit_billings')->insert([
        'id' => $billingId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'patient_visit_id' => $visitId,
        'visit_payer_id' => $payerId,
        'payer_type' => PayerType::CASH->value,
        'gross_amount' => 0,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => 0,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = resolve(CreateFacilityService::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'Ward Procedure',
        'category' => FacilityServiceCategory::PROCEDURE,
        'description' => 'Ward procedure service',
        'cost_price' => 15,
        'unit_price' => 55,
        'is_billable' => true,
        'is_active' => true,
    ]);

    ChargeMaster::query()
        ->whereKey($service->charge_master_id)
        ->update([
            'unit_price' => 80,
            'updated_at' => now(),
        ]);

    $order = FacilityServiceOrder::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_id' => $visitId,
        'consultation_id' => null,
        'facility_service_id' => $service->id,
        'ordered_by' => $staff->id,
        'status' => FacilityServiceOrderStatus::PENDING,
        'ordered_at' => now(),
    ]);

    resolve(SyncFacilityServiceOrderCharge::class)->handle($order->refresh());

    $charge = VisitCharge::query()->where('patient_visit_id', $visitId)->first();

    expect($charge)->not()->toBeNull()
        ->and($charge?->charge_master_id)->toBe($service->charge_master_id)
        ->and($charge?->charge_code)->toBe($service->service_code)
        ->and($charge?->line_total)->toBe('80.00');
});
