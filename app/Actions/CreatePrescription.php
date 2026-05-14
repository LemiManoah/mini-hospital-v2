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
use App\Notifications\PrescriptionCreatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreatePrescription
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
        private NotifyUsersWithPermission $notifyUsersWithPermission,
        private RecordAuditActivity $recordAuditActivity,
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

        $prescription = DB::transaction(function () use ($visit, $consultation, $data, $staffId, $inventoryItems): Prescription {
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
                'items.inventoryItem:id,tenant_id,item_type,name,generic_name,brand_name,strength,dosage_form,default_selling_price,charge_master_id,is_active,created_by',
                'items.inventoryItem.chargeMaster',
            ]);

            $this->ensureVisitInProgress($visit);

            return $prescription;
        });

        $this->recordAuditActivity->handle(
            logName: 'pharmacy',
            event: 'prescription.created',
            subject: $prescription,
            description: 'Prescription created.',
            tenantId: $visit->tenant_id,
            branchId: $visit->facility_branch_id,
            staffId: $staffId,
            newValues: [
                'visit_id' => $visit->id,
                'consultation_id' => $consultation?->id,
                'prescription_id' => $prescription->id,
                'item_count' => $prescription->items->count(),
                'status' => $prescription->status,
            ],
            metadata: [
                'is_discharge_medication' => $prescription->is_discharge_medication,
                'is_long_term' => $prescription->is_long_term,
                'causer_user_id' => Auth::id(),
            ],
        );

        if ($visit->tenant_id !== null) {
            $this->notifyUsersWithPermission->handle(
                $visit->tenant_id,
                ['pharmacy_dispensing.view', 'pharmacy_dispensing.create'],
                new PrescriptionCreatedNotification($prescription),
            );
        }

        return $prescription;
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
