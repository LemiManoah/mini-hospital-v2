<?php

declare(strict_types=1);

use App\Data\Inventory\CreateInventoryItemDTO;
use App\Enums\InventoryItemType;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes create inventory item input into a typed dto', function (): void {
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

    $dto = CreateInventoryItemDTO::fromRequest($request([
        'item_type' => InventoryItemType::DRUG->value,
        'name' => 'Paracetamol',
        'generic_name' => 'Paracetamol',
        'minimum_stock_level' => 5.0,
        'reorder_level' => 10.0,
        'default_purchase_price' => '12.50',
        'default_selling_price' => '15.00',
        'expires' => true,
        'is_controlled' => false,
        'therapeutic_classes' => ['analgesic', 'antipyretic'],
        'is_active' => true,
    ]));

    expect($dto->itemType)->toBe(InventoryItemType::DRUG->value)
        ->and($dto->genericName)->toBe('Paracetamol')
        ->and($dto->minimumStockLevel)->toBe(5.0)
        ->and($dto->defaultPurchasePrice)->toBe('12.50')
        ->and($dto->therapeuticClasses)->toBe(['analgesic', 'antipyretic']);
});
