<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\InventoryBatch;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreStockAdjustmentRequest extends FormRequest
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
            'adjustment_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'string',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'items.*.inventory_batch_id' => ['nullable', 'string', 'exists:inventory_batches,id'],
            'items.*.quantity_delta' => ['required', 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
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
                    $quantityDelta = $item['quantity_delta'] ?? null;

                    if (! is_string($inventoryItemId) || $inventoryItemId === '' || ! is_numeric($quantityDelta)) {
                        continue;
                    }

                    $delta = (float) $quantityDelta;

                    if ($delta < 0 && (! is_string($inventoryBatchId) || $inventoryBatchId === '')) {
                        $validator->errors()->add(
                            "items.$index.inventory_batch_id",
                            'Select the batch to adjust down when recording a stock loss.',
                        );

                        continue;
                    }

                    if (is_string($inventoryBatchId) && $inventoryBatchId !== '') {
                        $batch = InventoryBatch::query()
                            ->whereKey($inventoryBatchId)
                            ->where('branch_id', $activeBranchId)
                            ->first();

                        if (! $batch instanceof InventoryBatch) {
                            $validator->errors()->add(
                                "items.$index.inventory_batch_id",
                                'The selected batch is not available in the active branch.',
                            );

                            continue;
                        }

                        if ($batch->inventory_location_id !== $locationId || $batch->inventory_item_id !== $inventoryItemId) {
                            $validator->errors()->add(
                                "items.$index.inventory_batch_id",
                                'The selected batch does not belong to the selected location and item.',
                            );
                        }

                        if ($delta < 0 && abs($delta) > (float) ($batchBalances[$inventoryBatchId] ?? 0.0)) {
                            $validator->errors()->add(
                                "items.$index.quantity_delta",
                                'The stock loss cannot exceed the selected batch balance.',
                            );
                        }

                        continue;
                    }

                    if ($delta < 0 && abs($delta) > (float) ($locationBalances[$inventoryItemId] ?? 0.0)) {
                        $validator->errors()->add(
                            "items.$index.quantity_delta",
                            'The stock loss cannot exceed the current location balance.',
                        );
                    }
                }
            },
        ];
    }
}
