<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreatePrescriptionDTO;
use App\Data\Clinical\CreatePrescriptionItemDTO;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\Prescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CreatePrescription
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    public function handle(Consultation|PatientVisit $context, CreatePrescriptionDTO $data, string $staffId): Prescription
    {
        [$visit, $consultation] = $this->resolveContext($context);

        /** @var Collection<int, InventoryItem> $inventoryItems */
        $inventoryItems = InventoryItem::query()
            ->drugs()
            ->whereIn('id', array_map(
                static fn (CreatePrescriptionItemDTO $item): string => $item->inventoryItemId,
                $data->items,
            ))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($visit, $consultation, $data, $staffId, $inventoryItems): Prescription {
            $prescription = Prescription::query()->create([
                'visit_id' => $visit->id,
                'consultation_id' => $consultation?->id,
                'prescribed_by' => $staffId,
                'prescription_date' => now(),
                'is_discharge_medication' => $data->isDischargeMedication,
                'is_long_term' => $data->isLongTerm,
                'primary_diagnosis' => $data->primaryDiagnosis ?? $consultation?->primary_diagnosis,
                'pharmacy_notes' => $data->pharmacyNotes,
                'status' => 'pending',
            ]);

            foreach ($data->items as $item) {
                $inventoryItem = $inventoryItems->get($item->inventoryItemId);
                if ($inventoryItem === null) {
                    continue;
                }

                $prescription->items()->create([
                    'inventory_item_id' => $inventoryItem->id,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'route' => $item->route,
                    'duration_days' => $item->durationDays,
                    'quantity' => $item->quantity,
                    'instructions' => $item->instructions,
                    'is_prn' => $item->isPrn,
                    'prn_reason' => $item->prnReason,
                    'is_external_pharmacy' => $item->isExternalPharmacy,
                    'status' => 'pending',
                ]);
            }

            $prescription = $prescription->loadMissing([
                'prescribedBy:id,first_name,last_name',
                'items.inventoryItem:id,generic_name,brand_name,strength,dosage_form',
            ]);

            $this->ensureVisitInProgress($visit);

            return $prescription;
        });
    }

    /**
     * @return array{0: PatientVisit, 1: Consultation|null}
     */
    private function resolveContext(Consultation|PatientVisit $context): array
    {
        if ($context instanceof Consultation) {
            return [$context->visit()->firstOrFail(), $context];
        }

        return [$context, $context->consultation];
    }

    private function ensureVisitInProgress(PatientVisit $visit): void
    {
        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }
    }
}
