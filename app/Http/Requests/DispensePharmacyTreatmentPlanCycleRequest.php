<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\PharmacyTreatmentPlan;
use App\Models\PharmacyTreatmentPlanCycle;
use App\Models\PharmacyTreatmentPlanItem;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryStockLedger;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class DispensePharmacyTreatmentPlanCycleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'inventory_location_id' => ['required', 'string', 'exists:inventory_locations,id'],
            'dispensed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pharmacy_treatment_plan_item_id' => ['required', 'string', 'distinct', 'exists:pharmacy_treatment_plan_items,id'],
            'items.*.dispensed_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.external_pharmacy' => ['nullable', 'boolean'],
            'items.*.external_reason' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.allocations' => ['nullable', 'array'],
            'items.*.allocations.*.inventory_batch_id' => ['required', 'string'],
            'items.*.allocations.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branchId = BranchContext::getActiveBranchId();
                $treatmentPlan = $this->route('treatmentPlan');
                $cycle = $this->route('cycle');
                $locationId = $this->input('inventory_location_id');
                $items = $this->input('items', []);

                if (
                    ! $treatmentPlan instanceof PharmacyTreatmentPlan
                    || ! $cycle instanceof PharmacyTreatmentPlanCycle
                    || ! is_string($branchId)
                    || $branchId === ''
                    || ! is_string($locationId)
                    || $locationId === ''
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $treatmentPlan = PharmacyTreatmentPlan::query()
                    ->whereKey($treatmentPlan->id)
                    ->where('branch_id', $branchId)
                    ->where('status', PharmacyTreatmentPlanStatus::ACTIVE)
                    ->with([
                        'prescription.visit:id,tenant_id',
                        'items:id,pharmacy_treatment_plan_id,prescription_item_id,inventory_item_id,quantity_per_cycle',
                    ])
                    ->first();

                if (! $treatmentPlan instanceof PharmacyTreatmentPlan) {
                    $validator->errors()->add('items', 'This treatment plan is not available in the active branch.');

                    return;
                }

                if ($cycle->pharmacy_treatment_plan_id !== $treatmentPlan->id || $cycle->status !== PharmacyTreatmentPlanCycleStatus::PENDING) {
                    $validator->errors()->add('items', 'Only pending treatment cycles can be dispensed.');

                    return;
                }

                $inventoryLocationAccess = resolve(InventoryLocationAccess::class);

                if (! $inventoryLocationAccess->canAccessLocationForTypes($this->user(), $locationId, ['pharmacy'], $branchId)) {
                    $validator->errors()->add(
                        'inventory_location_id',
                        'You can only dispense scheduled treatment from pharmacy locations you manage.',
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

                $tenantId = $treatmentPlan->prescription?->visit?->tenant_id;
                $batchTrackingEnabled = is_string($tenantId) && $tenantId !== ''
                    ? resolve(TenantGeneralSettings::class)->boolean($tenantId, 'enable_batch_tracking_when_dispensing')
                    : true;

                $planItems = $treatmentPlan->items->keyBy('id');
                $batchBalances = resolve(InventoryStockLedger::class)
                    ->summarizeByBatch($branchId)
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

                    $planItemId = $item['pharmacy_treatment_plan_item_id'] ?? null;
                    $dispensedQuantity = $item['dispensed_quantity'] ?? null;
                    $externalPharmacy = filter_var($item['external_pharmacy'] ?? false, FILTER_VALIDATE_BOOL);
                    $allocations = $item['allocations'] ?? [];

                    if (! is_string($planItemId) || ! is_numeric($dispensedQuantity)) {
                        continue;
                    }

                    $planItem = $planItems->get($planItemId);

                    if (! $planItem instanceof PharmacyTreatmentPlanItem) {
                        $validator->errors()->add(
                            sprintf('items.%d.pharmacy_treatment_plan_item_id', $index),
                            'Each dispense line must belong to the selected treatment plan.',
                        );

                        continue;
                    }

                    $expectedQuantity = round((float) $planItem->quantity_per_cycle, 3);
                    $localQuantity = round((float) $dispensedQuantity, 3);

                    if ($localQuantity > $expectedQuantity + 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%d.dispensed_quantity', $index),
                            'Cycle dispense quantity cannot exceed the scheduled quantity for this treatment line.',
                        );
                    }

                    if ($localQuantity < $expectedQuantity - 0.0005 && ! $externalPharmacy) {
                        $validator->errors()->add(
                            sprintf('items.%d.external_pharmacy', $index),
                            'Any scheduled quantity not dispensed locally must be marked for external pharmacy fulfilment before completing this cycle.',
                        );
                    }

                    if ($externalPharmacy && (! is_string($item['external_reason'] ?? null) || mb_trim((string) $item['external_reason']) === '')) {
                        $validator->errors()->add(
                            sprintf('items.%d.external_reason', $index),
                            'Add a reason when part of a scheduled treatment cycle is handled externally.',
                        );
                    }

                    if (! $batchTrackingEnabled || $localQuantity <= 0) {
                        continue;
                    }

                    if (! is_array($allocations) || $allocations === []) {
                        $validator->errors()->add(
                            sprintf('items.%d.allocations', $index),
                            'Select source batches for each locally dispensed staged-treatment line.',
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
                            || $batch->inventory_item_id !== $planItem->inventory_item_id
                        ) {
                            $validator->errors()->add(
                                sprintf('items.%d.allocations.%d.inventory_batch_id', $index, $allocationIndex),
                                'Each selected batch must belong to the dispense location and medication item.',
                            );
                        }

                        if ($batch->expiry_date !== null && $batch->expiry_date->startOfDay()->isBefore(now()->startOfDay())) {
                            $validator->errors()->add(
                                sprintf('items.%d.allocations.%d.inventory_batch_id', $index, $allocationIndex),
                                'Expired batches cannot be used for staged treatment dispensing.',
                            );
                        }

                        $allocationQuantity = round((float) $quantity, 3);
                        $allocationTotal += $allocationQuantity;
                        $batchUsage[$batchId] = ($batchUsage[$batchId] ?? 0.0) + $allocationQuantity;
                    }

                    if (abs($allocationTotal - $localQuantity) > 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%d.allocations', $index),
                            'Allocated batch quantities must add up to the locally dispensed quantity.',
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
