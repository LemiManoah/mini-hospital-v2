<?php

declare(strict_types=1);

use App\Data\Inventory\UpdateInventoryItemDTO;
use App\Enums\InventoryItemType;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes update inventory item input into a typed dto', function (): void {
    /**
     * @param  array<string, mixed>  $validated
     */
    $request = static fn (array $validated): FormRequest => new class($validated) extends FormRequest
    {
        /**
         * @param  array<string, mixed>  $validatedInput
         */
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        /**
         * @return array<string, mixed>
         */
        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };

    $dto = UpdateInventoryItemDTO::fromRequest($request([
        'item_type' => InventoryItemType::SUPPLY->value,
        'name' => 'Gloves',
        'generic_name' => null,
        'minimum_stock_level' => 20.0,
        'reorder_level' => 40.0,
        'default_purchase_price' => 2.5,
        'default_selling_price' => null,
        'expires' => false,
        'is_controlled' => false,
        'is_active' => true,
    ]));

    expect($dto->itemType)->toBe(InventoryItemType::SUPPLY->value)
        ->and($dto->name)->toBe('Gloves')
        ->and($dto->minimumStockLevel)->toBe(20.0)
        ->and($dto->defaultPurchasePrice)->toBe(2.5);
});
