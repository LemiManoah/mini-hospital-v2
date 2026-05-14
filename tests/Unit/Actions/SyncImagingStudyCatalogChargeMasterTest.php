<?php

declare(strict_types=1);

use App\Actions\SyncImagingStudyCatalogChargeMaster;
use App\Enums\BillableItemType;
use App\Enums\ImagingModality;
use App\Models\ChargeMaster;
use App\Models\ImagingStudyCatalog;
use App\Models\Tenant;

it('creates and updates a charge master row for active imaging studies', function (): void {
    $tenant = Tenant::factory()->create();
    $study = ImagingStudyCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => null,
        'code' => 'IMG-CXR',
        'name' => 'Chest X-Ray',
        'modality' => ImagingModality::XRAY,
        'body_part' => 'Chest',
        'base_price' => 35000,
        'is_active' => true,
    ]);

    $chargeMaster = resolve(SyncImagingStudyCatalogChargeMaster::class)->handle($study);

    expect($chargeMaster)->toBeInstanceOf(ChargeMaster::class)
        ->and($study->fresh()->charge_master_id)->toBe($chargeMaster?->id)
        ->and($chargeMaster?->billable_type)->toBe(BillableItemType::IMAGING)
        ->and($chargeMaster?->billable_id)->toBe($study->id)
        ->and((float) $chargeMaster?->unit_price)->toBe(35000.0);

    $study->forceFill([
        'name' => 'Chest X-Ray Updated',
        'base_price' => 40000,
    ])->save();

    $updatedChargeMaster = resolve(SyncImagingStudyCatalogChargeMaster::class)->handle($study->fresh());

    expect($updatedChargeMaster?->id)->toBe($chargeMaster?->id)
        ->and($updatedChargeMaster?->description)->toBe('Chest X-Ray Updated')
        ->and((float) $updatedChargeMaster?->unit_price)->toBe(40000.0);
});

it('deactivates the linked charge master row for inactive imaging studies', function (): void {
    $tenant = Tenant::factory()->create();
    $study = ImagingStudyCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => null,
        'code' => 'IMG-ABD-US',
        'name' => 'Abdominal Ultrasound',
        'modality' => ImagingModality::ULTRASOUND,
        'body_part' => 'Abdomen',
        'base_price' => 45000,
        'is_active' => true,
    ]);

    $chargeMaster = resolve(SyncImagingStudyCatalogChargeMaster::class)->handle($study);

    $study->forceFill(['is_active' => false])->save();

    $result = resolve(SyncImagingStudyCatalogChargeMaster::class)->handle($study->fresh());

    expect($result)->toBeNull()
        ->and($chargeMaster?->fresh()?->is_active)->toBeFalse();
});
