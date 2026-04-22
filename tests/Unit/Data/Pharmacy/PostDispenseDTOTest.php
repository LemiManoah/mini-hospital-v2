<?php

declare(strict_types=1);

use App\Data\Pharmacy\PostDispenseDTO;
use Illuminate\Foundation\Http\FormRequest;

function postDispenseRequest(array $validated): FormRequest
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

it('normalizes a post dispense dto from validated input', function (): void {
    $dto = PostDispenseDTO::fromRequest(postDispenseRequest([
        'items' => [[
            'dispensing_record_item_id' => 'record-item-1',
            'allocations' => [[
                'inventory_batch_id' => 'batch-1',
                'quantity' => '3',
            ]],
        ]],
    ]));

    expect($dto->items)->toHaveCount(1)
        ->and($dto->items[0]->dispensingRecordItemId)->toBe('record-item-1')
        ->and($dto->items[0]->allocations)->toHaveCount(1)
        ->and($dto->items[0]->allocations[0]->inventoryBatchId)->toBe('batch-1')
        ->and($dto->items[0]->allocations[0]->quantity)->toBe(3.0);
});
