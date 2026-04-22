<?php

declare(strict_types=1);

use App\Data\Clinical\CreateLabRequestItemConsumableDTO;
use Illuminate\Foundation\Http\FormRequest;

it('normalizes create lab request item consumable input into a typed dto', function (): void {
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

    $data = CreateLabRequestItemConsumableDTO::fromRequest($request([
        'inventory_item_id' => ' item-1 ',
        'consumable_name' => 'EDTA Tube',
        'unit_label' => ' pcs ',
        'quantity' => '2.5',
        'unit_cost' => '1500',
        'notes' => '  Used for CBC  ',
        'used_at' => ' 2026-04-22 09:30:00 ',
    ]));

    expect($data->inventoryItemId)->toBe('item-1')
        ->and($data->consumableName)->toBe('EDTA Tube')
        ->and($data->unitLabel)->toBe('pcs')
        ->and($data->quantity)->toBe(2.5)
        ->and($data->unitCost)->toBe(1500.0)
        ->and($data->notes)->toBe('Used for CBC')
        ->and($data->usedAt)->toBe('2026-04-22 09:30:00');
});
