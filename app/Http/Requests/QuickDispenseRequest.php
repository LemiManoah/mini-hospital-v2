<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\InventoryBatch;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryStockLedger;
use Closure;
use Illuminate\Validation\Validator;

final class QuickDispenseRequest extends StoreDispenseRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'items.*.allocations' => ['nullable', 'array'],
            'items.*.allocations.*.inventory_batch_id' => ['required', 'string'],
            'items.*.allocations.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);
    }

    /**
     * @return array<int, Closure(Validator):void>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                $activeBranchId = BranchContext::getActiveBranchId();
                $prescription = $this->route('prescription');
                $locationId = $this->input('inventory_location_id');
                $items = $this->input('items', []);

                if (
                    ! $prescription instanceof Prescription
                    || ! is_string($activeBranchId)
                    || $activeBranchId === ''
                    || ! is_string($locationId)
                    || $locationId === ''
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $batchTrackingEnabled = resolve(TenantGeneralSettings::class)->boolean(
                    $prescription->visit->tenant_id,
                    'enable_batch_tracking_when_dispensing',
                );

                if (! $batchTrackingEnabled) {
                    return;
                }

                $prescriptionItems = $prescription->items()->get()->keyBy('id');
                $batchBalances = resolve(InventoryStockLedger::class)
                    ->summarizeByBatch($activeBranchId)
                    ->filter(
                        static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId
                            && $balance['quantity'] > 0
                    )
                    ->mapWithKeys(static fn (array $balance): array => [
                        $balance['inventory_batch_id'] => $balance['quantity'],
                    ]);

                $batchUsage = [];

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $prescriptionItemId = $item['prescription_item_id'] ?? null;
                    $dispensedQuantity = $item['dispensed_quantity'] ?? null;
                    $allocations = $item['allocations'] ?? [];

                    if (
                        ! is_string($prescriptionItemId)
                        || ! is_numeric($dispensedQuantity)
                        || (float) $dispensedQuantity <= 0
                    ) {
                        continue;
                    }

                    /** @var PrescriptionItem|null $prescriptionItem */
                    $prescriptionItem = $prescriptionItems->get($prescriptionItemId);
                    if (! $prescriptionItem instanceof PrescriptionItem) {
                        continue;
                    }

                    if (! is_array($allocations) || $allocations === []) {
                        $validator->errors()->add(
                            sprintf('items.%d.allocations', $index),
                            'Select source batches for each dispensed medication line.',
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

                        if (! is_string($batchId) || $batchId === '' || ! is_numeric($quantity)) {
                            continue;
                        }

                        $batch = InventoryBatch::query()->find($batchId);
                        if (! $batch instanceof InventoryBatch) {
                            $validator->errors()->add(
                                sprintf('items.%d.allocations.%d.inventory_batch_id', $index, $allocationIndex),
                                'One of the selected pharmacy batches is invalid.',
                            );

                            continue;
                        }

                        if (
                            $batch->inventory_location_id !== $locationId
                            || $batch->inventory_item_id !== $prescriptionItem->inventory_item_id
                        ) {
                            $validator->errors()->add(
                                sprintf('items.%d.allocations.%d.inventory_batch_id', $index, $allocationIndex),
                                'Each selected batch must belong to the dispense location and medication item.',
                            );
                        }

                        if ($batch->expiry_date !== null && $batch->expiry_date->startOfDay()->isBefore(now()->startOfDay())) {
                            $validator->errors()->add(
                                sprintf('items.%d.allocations.%d.inventory_batch_id', $index, $allocationIndex),
                                'Expired batches cannot be used for dispensing.',
                            );
                        }

                        $allocationQuantity = round((float) $quantity, 3);
                        $allocationTotal += $allocationQuantity;
                        $batchUsage[$batchId] = ($batchUsage[$batchId] ?? 0.0) + $allocationQuantity;
                    }

                    if (abs($allocationTotal - round((float) $dispensedQuantity, 3)) > 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%d.allocations', $index),
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
