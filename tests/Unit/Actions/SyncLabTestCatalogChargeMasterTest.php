<?php

declare(strict_types=1);

use App\Actions\SyncLabTestCatalogChargeMaster;
use App\Enums\BillableItemType;
use App\Models\ChargeMaster;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\Tenant;

it('creates and updates a charge master row for a lab test catalog item', function (): void {
    $tenant = Tenant::factory()->create();

    $category = LabTestCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Hematology',
        'is_active' => true,
    ]);

    $resultType = LabResultType::query()->create([
        'tenant_id' => $tenant->id,
        'code' => 'free_entry',
        'name' => 'Free Entry',
        'is_active' => true,
    ]);

    $labTest = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => 'CBC',
        'test_name' => 'Complete Blood Count',
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 25000,
        'is_active' => true,
    ]);

    $chargeMaster = resolve(SyncLabTestCatalogChargeMaster::class)->handle($labTest);

    expect($chargeMaster)->toBeInstanceOf(ChargeMaster::class)
        ->and($labTest->fresh()->charge_master_id)->toBe($chargeMaster?->id)
        ->and($chargeMaster?->item_code)->toBe('CBC')
        ->and($chargeMaster?->description)->toBe('Complete Blood Count')
        ->and($chargeMaster?->billable_type)->toBe(BillableItemType::TEST)
        ->and($chargeMaster?->billable_id)->toBe($labTest->id)
        ->and((float) $chargeMaster?->unit_price)->toBe(25000.0);

    $labTest->forceFill([
        'test_name' => 'CBC Updated',
        'base_price' => 30000,
    ])->save();

    $updatedChargeMaster = resolve(SyncLabTestCatalogChargeMaster::class)->handle($labTest->fresh());

    expect($updatedChargeMaster?->id)->toBe($chargeMaster?->id)
        ->and($updatedChargeMaster?->description)->toBe('CBC Updated')
        ->and((float) $updatedChargeMaster?->unit_price)->toBe(30000.0);
});
