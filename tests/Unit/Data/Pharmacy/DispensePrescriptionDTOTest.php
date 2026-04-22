<?php

declare(strict_types=1);

use App\Data\Pharmacy\DispensePrescriptionDTO;
use Illuminate\Foundation\Http\FormRequest;

function dispensePrescriptionRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
    {
        public function __construct(private readonly array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };
}

it('normalizes a direct dispense dto and can derive the draft-create dto', function (): void {
    $dto = DispensePrescriptionDTO::fromRequest(dispensePrescriptionRequest([
        'inventory_location_id' => 'location-2',
        'dispensed_at' => '2026-04-22 11:00:00',
        'notes' => '  Direct from queue  ',
        'items' => [[
            'prescription_item_id' => 'line-2',
            'dispensed_quantity' => 4,
            'external_pharmacy' => false,
            'external_reason' => null,
            'notes' => '',
            'substitution_inventory_item_id' => null,
            'allocations' => [[
                'inventory_batch_id' => 'batch-2',
                'quantity' => 4,
            ]],
        ]],
    ]));

    $createDto = $dto->toCreateDispensingRecordDTO();

    expect($dto->notes)->toBe('Direct from queue')
        ->and($dto->items[0]->allocations)->toHaveCount(1)
        ->and($createDto->inventoryLocationId)->toBe('location-2')
        ->and($createDto->items[0]->prescriptionItemId)->toBe('line-2')
        ->and($createDto->items[0]->dispensedQuantity)->toBe(4.0);
});
