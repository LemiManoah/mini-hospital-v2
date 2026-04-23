<?php

declare(strict_types=1);

use App\Actions\UpdateInventoryItem;
use App\Data\Inventory\UpdateInventoryItemDTO;
use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates an inventory item from a typed dto', function (): void {
    actingAs(User::factory()->create());

    $inventoryItem = InventoryItem::factory()->create([
        'item_type' => InventoryItemType::SUPPLY,
        'name' => 'Old Gloves',
    ]);

    $updatedItem = resolve(UpdateInventoryItem::class)->handle(
        $inventoryItem,
        new UpdateInventoryItemDTO(
            itemType: InventoryItemType::SUPPLY->value,
            name: 'Sterile Gloves',
            genericName: null,
            brandName: null,
            description: 'Sterile disposable gloves',
            unitId: null,
            category: null,
            strength: null,
            dosageForm: null,
            minimumStockLevel: 12.0,
            reorderLevel: 24.0,
            defaultPurchasePrice: '6.00',
            defaultSellingPrice: '8.00',
            manufacturer: 'Acme',
            expires: false,
            isControlled: false,
            scheduleClass: null,
            therapeuticClasses: null,
            contraindications: null,
            interactions: null,
            sideEffects: null,
            isActive: true,
        ),
    );

    expect($updatedItem->name)->toBe('Sterile Gloves')
        ->and($updatedItem->description)->toBe('Sterile disposable gloves');
});
