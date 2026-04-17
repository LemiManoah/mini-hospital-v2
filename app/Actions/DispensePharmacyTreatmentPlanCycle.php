<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Models\DispensingRecord;
use App\Models\PharmacyTreatmentPlan;
use App\Models\PharmacyTreatmentPlanCycle;
use App\Models\PharmacyTreatmentPlanItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DispensePharmacyTreatmentPlanCycle
{
    public function __construct(
        private DispensePrescription $dispensePrescription,
    ) {}

    public function handle(
        PharmacyTreatmentPlan $treatmentPlan,
        PharmacyTreatmentPlanCycle $cycle,
        array $attributes,
        array $items,
    ): DispensingRecord {
        return DB::transaction(function () use ($treatmentPlan, $cycle, $attributes, $items): DispensingRecord {
            $treatmentPlan = PharmacyTreatmentPlan::query()
                ->with(['prescription.items', 'items', 'cycles'])
                ->lockForUpdate()
                ->findOrFail($treatmentPlan->id);

            $cycle = PharmacyTreatmentPlanCycle::query()
                ->lockForUpdate()
                ->findOrFail($cycle->id);

            if ($cycle->pharmacy_treatment_plan_id !== $treatmentPlan->id) {
                throw ValidationException::withMessages([
                    'items' => 'The selected treatment cycle does not belong to this plan.',
                ]);
            }

            if ($treatmentPlan->status !== PharmacyTreatmentPlanStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'items' => 'Only active treatment plans can be dispensed.',
                ]);
            }

            if ($cycle->status !== PharmacyTreatmentPlanCycleStatus::PENDING) {
                throw ValidationException::withMessages([
                    'items' => 'Only pending treatment cycles can be dispensed.',
                ]);
            }

            $planItems = $treatmentPlan->items->keyBy('id');

            $dispenseItems = collect($items)
                ->map(function (array $item) use ($planItems): ?array {
                    $planItem = $planItems->get((string) ($item['pharmacy_treatment_plan_item_id'] ?? ''));

                    if (! $planItem instanceof PharmacyTreatmentPlanItem) {
                        return null;
                    }

                    return [
                        'prescription_item_id' => $planItem->prescription_item_id,
                        'dispensed_quantity' => $item['dispensed_quantity'] ?? 0,
                        'external_pharmacy' => (bool) ($item['external_pharmacy'] ?? false),
                        'external_reason' => $item['external_reason'] ?? '',
                        'notes' => $item['notes'] ?? '',
                        'allocations' => is_array($item['allocations'] ?? null) ? $item['allocations'] : [],
                    ];
                })
                ->filter()
                ->values()
                ->all();

            $recordNotes = trim(implode("\n", array_filter([
                sprintf('Treatment cycle %d', $cycle->cycle_number),
                is_string($attributes['notes'] ?? null) ? $attributes['notes'] : null,
            ])));

            $record = $this->dispensePrescription->handle(
                $treatmentPlan->prescription,
                [
                    ...$attributes,
                    'notes' => $recordNotes !== '' ? $recordNotes : null,
                ],
                $dispenseItems,
            );

            $cycle->update([
                'status' => PharmacyTreatmentPlanCycleStatus::COMPLETED,
                'completed_at' => $record->dispensed_at ?? now(),
                'dispensing_record_id' => $record->id,
                'notes' => $recordNotes !== '' ? $recordNotes : null,
            ]);

            foreach ($treatmentPlan->items as $planItem) {
                if (! $planItem instanceof PharmacyTreatmentPlanItem) {
                    continue;
                }

                $planItem->increment('completed_cycles');
            }

            $nextPendingCycle = $treatmentPlan->cycles()
                ->where('status', PharmacyTreatmentPlanCycleStatus::PENDING)
                ->orderBy('cycle_number')
                ->first();

            $treatmentPlan->update([
                'completed_cycles' => $treatmentPlan->cycles()
                    ->where('status', PharmacyTreatmentPlanCycleStatus::COMPLETED)
                    ->count(),
                'next_refill_date' => $nextPendingCycle?->scheduled_for,
                'status' => $nextPendingCycle instanceof PharmacyTreatmentPlanCycle
                    ? PharmacyTreatmentPlanStatus::ACTIVE
                    : PharmacyTreatmentPlanStatus::COMPLETED,
                'updated_by' => Auth::id(),
            ]);

            return $record;
        });
    }
}
