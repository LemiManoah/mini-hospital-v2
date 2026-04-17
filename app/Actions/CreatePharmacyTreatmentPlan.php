<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanFrequencyUnit;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Models\PharmacyTreatmentPlan;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreatePharmacyTreatmentPlan
{
    public function handle(Prescription $prescription, array $attributes, array $items): PharmacyTreatmentPlan
    {
        return DB::transaction(function () use ($prescription, $attributes, $items): PharmacyTreatmentPlan {
            $visit = $prescription->visit()->firstOrFail();
            $prescriptionItems = $prescription->items()->get()->keyBy('id');
            $startDate = Carbon::parse((string) $attributes['start_date'])->startOfDay();
            $totalAuthorizedCycles = max(1, (int) ($attributes['total_authorized_cycles'] ?? 1));
            $frequencyUnit = PharmacyTreatmentPlanFrequencyUnit::from((string) $attributes['frequency_unit']);
            $frequencyInterval = max(1, (int) ($attributes['frequency_interval'] ?? 1));

            $plan = PharmacyTreatmentPlan::query()->create([
                'tenant_id' => $visit->tenant_id,
                'branch_id' => $visit->facility_branch_id,
                'visit_id' => $visit->id,
                'prescription_id' => $prescription->id,
                'start_date' => $startDate->toDateString(),
                'frequency_unit' => $frequencyUnit,
                'frequency_interval' => $frequencyInterval,
                'total_authorized_cycles' => $totalAuthorizedCycles,
                'completed_cycles' => 0,
                'next_refill_date' => $startDate->toDateString(),
                'status' => PharmacyTreatmentPlanStatus::ACTIVE,
                'notes' => ($attributes['notes'] ?? '') !== '' ? $attributes['notes'] : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $prescriptionItem = $prescriptionItems->get((string) ($item['prescription_item_id'] ?? ''));

                if (! $prescriptionItem instanceof PrescriptionItem) {
                    continue;
                }

                $quantityPerCycle = round((float) ($item['quantity_per_cycle'] ?? 0), 3);
                $authorizedTotalQuantity = round($quantityPerCycle * $totalAuthorizedCycles, 3);

                $plan->items()->create([
                    'prescription_item_id' => $prescriptionItem->id,
                    'inventory_item_id' => $prescriptionItem->inventory_item_id,
                    'authorized_total_quantity' => $authorizedTotalQuantity,
                    'quantity_per_cycle' => $quantityPerCycle,
                    'total_cycles' => $totalAuthorizedCycles,
                    'completed_cycles' => 0,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                ]);
            }

            $scheduledFor = $startDate->copy();

            for ($cycleNumber = 1; $cycleNumber <= $totalAuthorizedCycles; $cycleNumber++) {
                $plan->cycles()->create([
                    'cycle_number' => $cycleNumber,
                    'scheduled_for' => $scheduledFor->toDateString(),
                    'status' => PharmacyTreatmentPlanCycleStatus::PENDING,
                ]);

                $scheduledFor = $frequencyUnit->advance($scheduledFor, $frequencyInterval);
            }

            return $plan->refresh()->load([
                'visit.patient',
                'prescription.prescribedBy',
                'items.inventoryItem',
                'items.prescriptionItem',
                'cycles',
            ]);
        });
    }
}
