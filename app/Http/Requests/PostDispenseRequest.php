<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DispensingRecordStatus;
use App\Models\DispensingRecord;
use App\Models\InventoryBatch;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryStockLedger;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class PostDispenseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.dispensing_record_item_id' => ['required', 'string', 'distinct'],
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
                $activeBranchId = BranchContext::getActiveBranchId();
                $dispensingRecord = $this->route('dispensingRecord');
                $items = $this->input('items', []);

                if (
                    ! $dispensingRecord instanceof DispensingRecord
                    || ! is_string($activeBranchId)
                    || $activeBranchId === ''
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                if ($dispensingRecord->branch_id !== $activeBranchId) {
                    $validator->errors()->add(
                        'dispensing_record',
                        'This dispensing record does not belong to the active branch.',
                    );

                    return;
                }

                if ($dispensingRecord->status !== DispensingRecordStatus::DRAFT) {
                    $validator->errors()->add(
                        'dispensing_record',
                        'Only draft dispensing records can be posted.',
                    );

                    return;
                }

                $dispensingRecord->loadMissing('items.inventoryItem');

                $batchTrackingEnabled = resolve(TenantGeneralSettings::class)->boolean(
                    $dispensingRecord->tenant_id,
                    'enable_batch_tracking_when_dispensing',
                );

                $batchBalances = resolve(InventoryStockLedger::class)
                    ->summarizeByBatch($dispensingRecord->branch_id)
                    ->filter(
                        static fn (array $balance): bool => $balance['inventory_location_id'] === $dispensingRecord->inventory_location_id
                            && $balance['quantity'] > 0
                    )
                    ->mapWithKeys(static fn (array $balance): array => [
                        $balance['inventory_batch_id'] => $balance['quantity'],
                    ]);

                $payloadByItem = collect($items)
                    ->filter(static fn (mixed $item): bool => is_array($item))
                    ->mapWithKeys(static fn (array $item): array => is_string($item['dispensing_record_item_id'] ?? null)
                        ? [$item['dispensing_record_item_id'] => $item]
                        : []);

                $batchUsage = [];

                foreach ($dispensingRecord->items as $recordItem) {
                    $dispensedQuantity = round((float) $recordItem->dispensed_quantity, 3);

                    if ($dispensedQuantity <= 0) {
                        continue;
                    }

                    $payload = $payloadByItem->get($recordItem->id);
                    $allocations = is_array($payload['allocations'] ?? null) ? $payload['allocations'] : [];

                    if ($batchTrackingEnabled && $allocations === []) {
                        $validator->errors()->add(
                            sprintf('items.%s.allocations', $recordItem->id),
                            'Select source batches for each dispensed medication line.',
                        );

                        continue;
                    }

                    if ($allocations === []) {
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
                        if ($batchId === '') {
                            continue;
                        }
                        if (! is_numeric($quantity)) {
                            continue;
                        }

                        $batch = InventoryBatch::query()->find($batchId);
                        if (! $batch instanceof InventoryBatch) {
                            $validator->errors()->add(
                                sprintf('items.%s.allocations.%s.inventory_batch_id', $recordItem->id, $allocationIndex),
                                'One of the selected pharmacy batches is invalid.',
                            );

                            continue;
                        }

                        if (
                            $batch->inventory_location_id !== $dispensingRecord->inventory_location_id
                            || $batch->inventory_item_id !== $recordItem->inventory_item_id
                        ) {
                            $validator->errors()->add(
                                sprintf('items.%s.allocations.%s.inventory_batch_id', $recordItem->id, $allocationIndex),
                                'Each selected batch must belong to the dispense location and medication item.',
                            );
                        }

                        if ($batch->expiry_date !== null && $batch->expiry_date->startOfDay()->isBefore(today())) {
                            $validator->errors()->add(
                                sprintf('items.%s.allocations.%s.inventory_batch_id', $recordItem->id, $allocationIndex),
                                'Expired batches cannot be used for dispensing.',
                            );
                        }

                        $allocationQuantity = round((float) $quantity, 3);
                        $allocationTotal += $allocationQuantity;
                        $batchUsage[$batchId] = ($batchUsage[$batchId] ?? 0.0) + $allocationQuantity;
                    }

                    if (abs($allocationTotal - $dispensedQuantity) > 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%s.allocations', $recordItem->id),
                            'Allocated batch quantities must add up to the dispensed quantity.',
                        );
                    }
                }

                foreach ($batchUsage as $batchId => $usedQuantity) {
                    if ($usedQuantity > (float) ($batchBalances[$batchId] ?? 0.0) + 0.0005) {
                        $validator->errors()->add(
                            'items',
                            'One of the selected pharmacy batches does not have enough available stock.',
                        );
                    }
                }
            },
        ];
    }
}
