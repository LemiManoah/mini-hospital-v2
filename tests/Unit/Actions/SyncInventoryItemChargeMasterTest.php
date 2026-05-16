<?php

declare(strict_types=1);

use App\Actions\SyncInventoryItemChargeMaster;
use App\Enums\BillableItemType;
use App\Enums\InventoryItemType;
use App\Models\ChargeMaster;
use App\Models\InventoryItem;

it('creates and updates a charge master row for active drug inventory items', function (): void {
    $drug = InventoryItem::factory()->create([
        'item_type' => InventoryItemType::DRUG,
        'name' => 'Paracetamol',
        'generic_name' => 'Paracetamol',
        'is_active' => true,
    ]);

    $chargeMaster = resolve(SyncInventoryItemChargeMaster::class)->handle($drug, 1500);

    expect($chargeMaster)->toBeInstanceOf(ChargeMaster::class)
        ->and($drug->fresh()->charge_master_id)->toBe($chargeMaster?->id)
        ->and($chargeMaster?->billable_type)->toBe(BillableItemType::DRUG)
        ->and($chargeMaster?->billable_id)->toBe($drug->id)
        ->and((float) $chargeMaster?->unit_price)->toBe(1500.0);

    $drug->forceFill([
        'generic_name' => 'Paracetamol Updated',
    ])->save();

    $updatedChargeMaster = resolve(SyncInventoryItemChargeMaster::class)->handle($drug->fresh(), 2000);

    expect($updatedChargeMaster?->id)->not()->toBe($chargeMaster?->id)
        ->and($drug->fresh()->charge_master_id)->toBe($updatedChargeMaster?->id)
        ->and($chargeMaster?->fresh()?->is_active)->toBeFalse()
        ->and($updatedChargeMaster?->description)->toBe('Paracetamol Updated')
        ->and((float) $updatedChargeMaster?->unit_price)->toBe(2000.0);
});

it('does not create a charge master row for non-drug inventory items', function (): void {
    $supply = InventoryItem::factory()->create([
        'item_type' => InventoryItemType::SUPPLY,
        'name' => 'Gloves',
        'is_active' => true,
    ]);

    $chargeMaster = resolve(SyncInventoryItemChargeMaster::class)->handle($supply, 500);

    expect($chargeMaster)->toBeNull()
        ->and($supply->fresh()->charge_master_id)->toBeNull();
});
