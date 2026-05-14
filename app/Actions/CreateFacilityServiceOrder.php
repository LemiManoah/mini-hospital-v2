<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateFacilityServiceOrderDTO;
use App\Enums\FacilityServiceOrderStatus;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\FacilityServiceOrder;
use App\Models\PatientVisit;
use App\Notifications\FacilityServiceOrderCreatedNotification;
use Illuminate\Validation\ValidationException;

final readonly class CreateFacilityServiceOrder
{
    public function __construct(
        private SyncFacilityServiceOrderCharge $syncFacilityServiceOrderCharge,
        private TransitionPatientVisitStatus $transitionStatus,
        private RecordAuditActivity $recordAuditActivity,
        private NotifyUsersWithPermission $notifyUsersWithPermission,
    ) {}

    public function handle(Consultation|PatientVisit $context, CreateFacilityServiceOrderDTO $data, string $staffId): FacilityServiceOrder
    {
        [$visit, $consultation] = $this->resolveContext($context);

        $hasPendingDuplicate = FacilityServiceOrder::query()
            ->where('visit_id', $visit->id)
            ->where('facility_service_id', $data->facilityServiceId)
            ->where('status', FacilityServiceOrderStatus::PENDING->value)
            ->exists();

        if ($hasPendingDuplicate) {
            throw ValidationException::withMessages([
                'facility_service_id' => 'This facility service already has a pending order for this visit. Remove or complete the existing order first.',
            ]);
        }

        $order = FacilityServiceOrder::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'consultation_id' => $consultation?->id,
            'facility_service_id' => $data->facilityServiceId,
            'ordered_by' => $staffId,
            'status' => FacilityServiceOrderStatus::PENDING,
            'ordered_at' => now(),
        ])->loadMissing([
            'service:id,tenant_id,name,service_code,category,is_billable,is_active,selling_price,charge_master_id,created_by',
            'orderedBy:id,first_name,last_name',
        ]);

        $this->syncFacilityServiceOrderCharge->handle($order);
        $this->ensureVisitInProgress($visit);

        $this->recordAuditActivity->handle(
            logName: 'clinical',
            event: 'service_order.created',
            subject: $order,
            description: 'Facility service order created.',
            tenantId: $order->tenant_id,
            branchId: $order->facility_branch_id,
            staffId: $staffId,
            newValues: [
                'visit_id' => $order->visit_id,
                'consultation_id' => $order->consultation_id,
                'facility_service_id' => $order->facility_service_id,
                'status' => $order->status->value,
            ],
        );

        if ($visit->tenant_id !== null) {
            $this->notifyUsersWithPermission->handle(
                $visit->tenant_id,
                ['facility_services.view', 'facility_services.update'],
                new FacilityServiceOrderCreatedNotification($order),
            );
        }

        return $order;
    }

    /**
     * @return array{0: PatientVisit, 1: Consultation|null}
     */
    private function resolveContext(Consultation|PatientVisit $context): array
    {
        if ($context instanceof Consultation) {
            /** @var PatientVisit $visit */
            $visit = $context->visit()->firstOrFail();

            return [$visit, $context];
        }

        /** @var Consultation|null $consultation */
        $consultation = $context->consultation;

        return [$context, $consultation];
    }

    private function ensureVisitInProgress(PatientVisit $visit): void
    {
        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }
    }
}
