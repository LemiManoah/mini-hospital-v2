<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use App\Models\InventoryBatch;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreInventoryReconciliationRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'inventory_location_id' => [
                'required',
                'string',
                Rule::exists('inventory_locations', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'reconciliation_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                'distinct',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'items.*.inventory_batch_id' => ['nullable', 'string', 'exists:inventory_batches,id'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, Closure(Validator):void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $activeBranchId = BranchContext::getActiveBranchId();
                $locationId = $this->input('inventory_location_id');
                $items = $this->input('items');

                if (
                    ! is_string($activeBranchId)
                    || $activeBranchId === ''
                    || ! is_string($locationId)
                    || $locationId === ''
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $ledger = resolve(InventoryStockLedger::class);

                /** @var array<string, float> $locationBalances */
                $locationBalances = $ledger
                    ->summarizeByLocation($activeBranchId)
                    ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId)
                    ->mapWithKeys(static fn (array $balance): array => [
                        $balance['inventory_item_id'] => $balance['quantity'],
                    ])
                    ->all();

                /** @var array<string, float> $batchBalances */
                $batchBalances = $ledger
                    ->summarizeByBatch($activeBranchId)
                    ->mapWithKeys(static fn (array $balance): array => [
                        $balance['inventory_batch_id'] => $balance['quantity'],
                    ])
                    ->all();

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $inventoryItemId = $item['inventory_item_id'] ?? null;
                    $inventoryBatchId = $item['inventory_batch_id'] ?? null;
                    $actualQuantity = $item['actual_quantity'] ?? null;
                    if (! is_string($inventoryItemId)) {
                        continue;
                    }
                    if ($inventoryItemId === '') {
                        continue;
                    }
                    if (! is_numeric($actualQuantity)) {
                        continue;
                    }

                    $expectedQuantity = (float) ($locationBalances[$inventoryItemId] ?? 0.0);
                    $variance = (float) $actualQuantity - $expectedQuantity;

                    if ($variance < 0 && (! is_string($inventoryBatchId) || $inventoryBatchId === '')) {
                        $validator->errors()->add(
                            sprintf('items.%s.inventory_batch_id', $index),
                            'Select the batch to reduce when the actual quantity is below the system quantity.',
                        );

                        continue;
                    }
                    if (! is_string($inventoryBatchId)) {
                        continue;
                    }
                    if ($inventoryBatchId === '') {
                        continue;
                    }

                    $batch = InventoryBatch::query()
                        ->whereKey($inventoryBatchId)
                        ->where('branch_id', $activeBranchId)
                        ->first();

                    if (! $batch instanceof InventoryBatch) {
                        $validator->errors()->add(
                            sprintf('items.%s.inventory_batch_id', $index),
                            'The selected batch is not available in the active branch.',
                        );

                        continue;
                    }

                    if ($batch->inventory_location_id !== $locationId || $batch->inventory_item_id !== $inventoryItemId) {
                        $validator->errors()->add(
                            sprintf('items.%s.inventory_batch_id', $index),
                            'The selected batch does not belong to the selected location and item.',
                        );
                    }

                    if ($variance < 0 && abs($variance) > (float) ($batchBalances[$inventoryBatchId] ?? 0.0)) {
                        $validator->errors()->add(
                            sprintf('items.%s.actual_quantity', $index),
                            'The reconciliation loss cannot exceed the selected batch balance.',
                        );
                    }
                }
            },
        ];
    }
}
