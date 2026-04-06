<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use App\Models\InventoryBatch;
use App\Models\InventoryRequisition;
use App\Support\InventoryStockLedger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class IssueInventoryRequisitionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'issued_notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_requisition_item_id' => ['required', 'string'],
            'items.*.issue_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.allocations' => ['nullable', 'array'],
            'items.*.allocations.*.inventory_batch_id' => ['required', 'string'],
            'items.*.allocations.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<int, Closure(Validator):void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $requisition = $this->route('requisition');
                $items = $this->input('items');

                if (
                    ! $requisition instanceof InventoryRequisition
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $requisitionItems = $requisition->items()->get()->keyBy('id');
                $ledger = resolve(InventoryStockLedger::class);
                $batchBalances = $ledger
                    ->summarizeByBatch($requisition->branch_id)
                    ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $requisition->source_inventory_location_id)
                    ->mapWithKeys(static fn (array $balance): array => [
                        $balance['inventory_batch_id'] => $balance['quantity'],
                    ]);

                $issueCount = 0;

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $lineId = $item['inventory_requisition_item_id'] ?? null;
                    $issueQuantity = $item['issue_quantity'] ?? null;
                    $allocations = $item['allocations'] ?? [];
                    if (! is_string($lineId)) {
                        continue;
                    }
                    if (! is_numeric($issueQuantity)) {
                        continue;
                    }

                    $line = $requisitionItems->get($lineId);
                    if ($line === null) {
                        $validator->errors()->add(
                            sprintf('items.%s.inventory_requisition_item_id', $index),
                            'One of the requisition lines is invalid.',
                        );

                        continue;
                    }

                    if ((float) $issueQuantity <= 0) {
                        continue;
                    }

                    $issueCount++;

                    if ((float) $issueQuantity > $line->remainingApprovedQuantity()) {
                        $validator->errors()->add(
                            sprintf('items.%s.issue_quantity', $index),
                            'Issue quantity cannot exceed the remaining approved quantity.',
                        );
                    }

                    if (! is_array($allocations) || $allocations === []) {
                        $validator->errors()->add(
                            sprintf('items.%s.allocations', $index),
                            'Select source batches for any quantity you are issuing.',
                        );

                        continue;
                    }

                    $allocationTotal = 0.0;

                    foreach ($allocations as $allocationIndex => $allocation) {
                        if (! is_array($allocation)) {
                            continue;
                        }

                        $batchId = $allocation['inventory_batch_id'] ?? null;
                        $quantity = $allocation['quantity'] ?? null;
                        if (! is_string($batchId)) {
                            continue;
                        }
                        if (! is_numeric($quantity)) {
                            continue;
                        }

                        $allocationTotal += (float) $quantity;

                        $batch = InventoryBatch::query()->find($batchId);
                        if (! $batch instanceof InventoryBatch) {
                            $validator->errors()->add(
                                sprintf('items.%s.allocations.%s.inventory_batch_id', $index, $allocationIndex),
                                'One of the selected source batches is invalid.',
                            );

                            continue;
                        }

                        if (
                            $batch->inventory_location_id !== $requisition->source_inventory_location_id
                            || $batch->inventory_item_id !== $line->inventory_item_id
                        ) {
                            $validator->errors()->add(
                                sprintf('items.%s.allocations.%s.inventory_batch_id', $index, $allocationIndex),
                                'Each source batch must match the requisition item and source location.',
                            );
                        }

                        if ((float) $quantity > (float) ($batchBalances[$batchId] ?? 0.0)) {
                            $validator->errors()->add(
                                sprintf('items.%s.allocations.%s.quantity', $index, $allocationIndex),
                                'Batch quantity cannot exceed the available stock in the source batch.',
                            );
                        }
                    }

                    if (abs($allocationTotal - (float) $issueQuantity) > 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%s.allocations', $index),
                            'Allocated batch quantities must add up to the issue quantity.',
                        );
                    }
                }

                if ($issueCount === 0) {
                    $validator->errors()->add('items', 'Issue at least one approved quantity.');
                }
            },
        ];
    }
}
