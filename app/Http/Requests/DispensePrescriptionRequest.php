<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryStockLedger;
use App\Support\PrescriptionDispenseProgress;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class DispensePrescriptionRequest extends FormRequest
{
    /**

     * @return array<int, callable(\\Illuminate\\Validation\\Validator): void>

     */

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branchId = BranchContext::getActiveBranchId();
                $prescriptionId = $this->route('prescription')?->getKey();
                $locationId = $this->input('inventory_location_id');
                $items = $this->input('items');

                if (
                    ! is_string($branchId)
                    || $branchId === ''
                    || ! is_string($prescriptionId)
                    || ! is_string($locationId)
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $prescription = Prescription::query()
                    ->whereKey($prescriptionId)
                    ->whereHas('visit', static fn (Builder $query): Builder => $query->where('facility_branch_id', $branchId))
                    ->with(['visit:id,tenant_id', 'items:id,prescription_id,inventory_item_id,quantity'])
                    ->first();

                if (! $prescription instanceof Prescription) {
                    $validator->errors()->add('items', 'This prescription is not available in the active branch.');

                    return;
                }

                $inventoryLocationAccess = resolve(InventoryLocationAccess::class);

                if (! $inventoryLocationAccess->canAccessLocationForTypes($this->user(), $locationId, ['pharmacy'], $branchId)) {
                    $validator->errors()->add(
                        'inventory_location_id',
                        'You can only prepare dispensing records in pharmacy locations you manage.',
                    );

                    return;
                }

                $location = InventoryLocation::query()->find($locationId);

                if (! $location instanceof InventoryLocation || ! $location->is_dispensing_point) {
                    $validator->errors()->add(
                        'inventory_location_id',
                        'The selected pharmacy location must be an active dispensing point.',
                    );
                }

                $prescriptionItems = $prescription->items->keyBy('id');
                $postedLineSummaries = resolve(PrescriptionDispenseProgress::class)
                    ->postedLineSummaries($prescription->id);
                $allowPartialDispense = resolve(TenantGeneralSettings::class)->boolean(
                    $prescription->visit->tenant_id,
                    'allow_partial_dispense',
                );
                $hasActionableLine = false;

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $prescriptionItemId = $item['prescription_item_id'] ?? null;
                    $dispensedQuantity = $item['dispensed_quantity'] ?? null;
                    $externalPharmacy = filter_var($item['external_pharmacy'] ?? false, FILTER_VALIDATE_BOOL);

                    if (! is_string($prescriptionItemId) || ! $prescriptionItems->has($prescriptionItemId)) {
                        $validator->errors()->add(
                            sprintf('items.%d.prescription_item_id', $index),
                            'Each dispense line must belong to the selected prescription.',
                        );

                        continue;
                    }

                    $prescriptionItem = $prescriptionItems->get($prescriptionItemId);

                    if (! $prescriptionItem instanceof PrescriptionItem) {
                        continue;
                    }

                    $remainingQuantity = max(
                        0,
                        round(
                            (float) $prescriptionItem->quantity
                            - (float) ($postedLineSummaries->get($prescriptionItem->id)['covered_quantity'] ?? 0.0),
                            3,
                        ),
                    );

                    if (is_numeric($dispensedQuantity) && (float) $dispensedQuantity > 0) {
                        $hasActionableLine = true;
                    }

                    if ($externalPharmacy) {
                        $hasActionableLine = true;

                        if (! is_string($item['external_reason'] ?? null) || mb_trim($item['external_reason']) === '') {
                            $validator->errors()->add(
                                sprintf('items.%d.external_reason', $index),
                                'Add a reason when marking a line for external pharmacy fulfilment.',
                            );
                        }
                    }

                    if (is_numeric($dispensedQuantity) && (float) $dispensedQuantity > $remainingQuantity) {
                        $validator->errors()->add(
                            sprintf('items.%d.dispensed_quantity', $index),
                            'Dispensed quantity cannot be greater than the remaining quantity on the prescription.',
                        );
                    }

                    if (
                        ! $allowPartialDispense
                        && is_numeric($dispensedQuantity)
                        && (float) $dispensedQuantity > 0
                        && (float) $dispensedQuantity < $remainingQuantity
                    ) {
                        $validator->errors()->add(
                            sprintf('items.%d.dispensed_quantity', $index),
                            'Partial dispensing is disabled for this facility. Use zero or the full remaining quantity.',
                        );
                    }

                    if (
                        $externalPharmacy
                        && is_numeric($dispensedQuantity)
                        && (float) $dispensedQuantity >= $remainingQuantity
                    ) {
                        $validator->errors()->add(
                            sprintf('items.%d.external_pharmacy', $index),
                            'External pharmacy can only be marked when part of the remaining quantity stays unfulfilled locally.',
                        );
                    }
                }

                if (! $hasActionableLine) {
                    $validator->errors()->add(
                        'items',
                        'Record at least one dispensed quantity or mark at least one line for external fulfilment.',
                    );
                }
            },
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

                    if (! is_string($prescriptionItemId)) {
                        continue;
                    }

                    if (! is_numeric($dispensedQuantity)) {
                        continue;
                    }

                    if ((float) $dispensedQuantity <= 0) {
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

                        if ($batch->expiry_date !== null && $batch->expiry_date->startOfDay()->isBefore(today())) {
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


