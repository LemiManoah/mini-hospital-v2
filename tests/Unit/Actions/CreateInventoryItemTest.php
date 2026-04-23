<?php

declare(strict_types=1);

use App\Actions\CreateInventoryItem;
use App\Data\Inventory\CreateInventoryItemDTO;
use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('creates an inventory item from a typed dto', function (): void {
    actingAs(User::factory()->create());

    $item = resolve(CreateInventoryItem::class)->handle(
        new CreateInventoryItemDTO(
            itemType: InventoryItemType::SUPPLY->value,
            name: 'Gloves',
            genericName: null,
            brandName: null,
            description: 'Disposable gloves',
            unitId: null,
            category: null,
            strength: null,
            dosageForm: null,
            minimumStockLevel: 10.0,
            reorderLevel: 20.0,
            defaultPurchasePrice: '5.50',
            defaultSellingPrice: '7.00',
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

    expect($item)->toBeInstanceOf(InventoryItem::class)
        ->and($item->name)->toBe('Gloves')
        ->and($item->description)->toBe('Disposable gloves');
});
