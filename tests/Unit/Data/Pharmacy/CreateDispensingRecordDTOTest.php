<?php

declare(strict_types=1);

use App\Data\Pharmacy\CreateDispensingRecordDTO;
use Illuminate\Foundation\Http\FormRequest;

function createDispensingRecordRequest(array $validated): FormRequest
{
    return new class($validated) extends FormRequest
    {
        public function __construct(private array $validatedInput)
        {
            parent::__construct();
        }

        public function validated($key = null, $default = null): array
        {
            return $this->validatedInput;
        }
    };
}

it('normalizes a create dispensing record dto from validated input', function (): void {
    $dto = CreateDispensingRecordDTO::fromRequest(createDispensingRecordRequest([
        'inventory_location_id' => 'location-1',
        'dispensed_at' => '2026-04-22 10:30:00',
        'notes' => '  Prepared for handover  ',
        'items' => [[
            'prescription_item_id' => 'line-1',
            'dispensed_quantity' => '2.5',
            'external_pharmacy' => false,
            'external_reason' => '   ',
            'notes' => '  In stock  ',
            'substitution_inventory_item_id' => '',
        ]],
    ]));

    expect($dto->inventoryLocationId)->toBe('location-1')
        ->and($dto->notes)->toBe('Prepared for handover')
        ->and($dto->items)->toHaveCount(1)
        ->and($dto->items[0]->dispensedQuantity)->toBe(2.5)
        ->and($dto->items[0]->externalReason)->toBeNull()
        ->and($dto->items[0]->substitutionInventoryItemId)->toBeNull();
});
