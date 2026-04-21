<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\InventoryLocation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryLocationAccess;
use App\Support\PrescriptionDispenseProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreDispenseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
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
            'dispensed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.prescription_item_id' => ['required', 'string', 'distinct', 'exists:prescription_items,id'],
            'items.*.dispensed_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.external_pharmacy' => ['nullable', 'boolean'],
            'items.*.external_reason' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.substitution_inventory_item_id' => [
                'nullable',
                'string',
                Rule::exists('inventory_items', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
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
                    ->with('items:id,prescription_id,quantity')
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

                $prescriptionVisit = $prescription->visit;

                if ($prescriptionVisit === null) {
                    $validator->errors()->add('items', 'The prescription visit context could not be resolved.');

                    return;
                }

                $prescriptionItems = $prescription->items->keyBy('id');
                $postedLineSummaries = resolve(PrescriptionDispenseProgress::class)
                    ->postedLineSummaries($prescription->id);
                $allowPartialDispense = resolve(TenantGeneralSettings::class)->boolean(
                    $prescriptionVisit->tenant_id,
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
        ];
    }
}
