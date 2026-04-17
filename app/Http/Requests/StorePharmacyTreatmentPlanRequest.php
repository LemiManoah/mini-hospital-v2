<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PharmacyTreatmentPlanFrequencyUnit;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Models\PharmacyTreatmentPlan;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\PrescriptionDispenseProgress;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StorePharmacyTreatmentPlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'frequency_unit' => ['required', Rule::enum(PharmacyTreatmentPlanFrequencyUnit::class)],
            'frequency_interval' => ['required', 'integer', 'min:1', 'max:365'],
            'total_authorized_cycles' => ['required', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.prescription_item_id' => ['required', 'string', 'distinct', 'exists:prescription_items,id'],
            'items.*.quantity_per_cycle' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branchId = BranchContext::getActiveBranchId();
                $prescription = $this->route('prescription');
                $items = $this->input('items', []);
                $totalAuthorizedCycles = $this->integer('total_authorized_cycles');

                if (
                    ! $prescription instanceof Prescription
                    || ! is_string($branchId)
                    || $branchId === ''
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $prescription = Prescription::query()
                    ->whereKey($prescription->id)
                    ->whereHas('visit', static fn (Builder $query): Builder => $query->where('facility_branch_id', $branchId))
                    ->with('items:id,prescription_id,inventory_item_id,quantity')
                    ->first();

                if (! $prescription instanceof Prescription) {
                    $validator->errors()->add('items', 'This prescription is not available in the active branch.');

                    return;
                }

                $existingActivePlan = PharmacyTreatmentPlan::query()
                    ->where('prescription_id', $prescription->id)
                    ->where('status', PharmacyTreatmentPlanStatus::ACTIVE)
                    ->exists();

                if ($existingActivePlan) {
                    $validator->errors()->add('items', 'This prescription already has an active treatment plan.');

                    return;
                }

                $progress = resolve(PrescriptionDispenseProgress::class)->postedLineSummaries($prescription->id);
                $prescriptionItems = $prescription->items->keyBy('id');

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $prescriptionItemId = $item['prescription_item_id'] ?? null;
                    $quantityPerCycle = $item['quantity_per_cycle'] ?? null;

                    if (! is_string($prescriptionItemId) || ! is_numeric($quantityPerCycle)) {
                        continue;
                    }

                    $prescriptionItem = $prescriptionItems->get($prescriptionItemId);

                    if (! $prescriptionItem instanceof PrescriptionItem) {
                        $validator->errors()->add(
                            sprintf('items.%d.prescription_item_id', $index),
                            'Each staged treatment line must belong to the selected prescription.',
                        );

                        continue;
                    }

                    $remainingQuantity = max(
                        0,
                        round(
                            (float) $prescriptionItem->quantity
                            - (float) ($progress->get($prescriptionItem->id)['covered_quantity'] ?? 0.0),
                            3,
                        ),
                    );

                    $requestedTotal = round((float) $quantityPerCycle * $totalAuthorizedCycles, 3);

                    if ((float) $quantityPerCycle > $remainingQuantity + 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%d.quantity_per_cycle', $index),
                            'Cycle quantity cannot be greater than the remaining prescription quantity.',
                        );
                    }

                    if ($requestedTotal > $remainingQuantity + 0.0005) {
                        $validator->errors()->add(
                            sprintf('items.%d.quantity_per_cycle', $index),
                            'Total scheduled treatment quantity cannot be greater than the remaining prescription quantity.',
                        );
                    }
                }
            },
        ];
    }
}
