<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateImagingOrderDTO;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\ImagingOrder;
use App\Models\ImagingStudyCatalog;
use App\Models\PatientVisit;
use App\Notifications\ImagingOrderCreatedNotification;

final readonly class CreateImagingOrder
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
        private RecordAuditActivity $recordAuditActivity,
        private NotifyUsersWithPermission $notifyUsersWithPermission,
        private SyncImagingOrderCharge $syncImagingOrderCharge,
    ) {}

    public function handle(Consultation|PatientVisit $context, CreateImagingOrderDTO $data, string $staffId): ImagingOrder
    {
        [$visit, $consultation] = $this->resolveContext($context);
        $studyCatalog = $this->studyCatalog($data);

        $order = ImagingOrder::query()->create([
            'visit_id' => $visit->id,
            'consultation_id' => $consultation?->id,
            'imaging_study_catalog_id' => $studyCatalog?->id,
            'requested_by' => $staffId,
            'modality' => $studyCatalog instanceof ImagingStudyCatalog ? $studyCatalog->modality : $data->modality,
            'body_part' => $studyCatalog instanceof ImagingStudyCatalog ? $studyCatalog->body_part : $data->bodyPart,
            'laterality' => $data->laterality,
            'clinical_history' => $data->clinicalHistory,
            'indication' => $data->indication,
            'priority' => $data->priority,
            'status' => 'requested',
            'requires_contrast' => $data->requiresContrast,
            'contrast_allergy_status' => $data->contrastAllergyStatus,
            'pregnancy_status' => $data->pregnancyStatus,
        ])->loadMissing(['requestedBy:id,first_name,last_name', 'studyCatalog.chargeMaster']);

        $this->syncImagingOrderCharge->handle($order);

        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }

        $this->recordAuditActivity->handle(
            logName: 'clinical',
            event: 'imaging_order.created',
            subject: $order,
            description: 'Imaging order created.',
            tenantId: $visit->tenant_id,
            branchId: $visit->facility_branch_id,
            staffId: $staffId,
            newValues: [
                'visit_id' => $order->visit_id,
                'consultation_id' => $order->consultation_id,
                'modality' => $order->modality->value,
                'body_part' => $order->body_part,
                'priority' => $order->priority->value,
            ],
        );

        if ($visit->tenant_id !== null) {
            $this->notifyUsersWithPermission->handle(
                $visit->tenant_id,
                ['imaging_orders.view', 'imaging_orders.update'],
                new ImagingOrderCreatedNotification($order),
            );
        }

        return $order;
    }

    private function studyCatalog(CreateImagingOrderDTO $data): ?ImagingStudyCatalog
    {
        if ($data->imagingStudyCatalogId === null) {
            return null;
        }

        return ImagingStudyCatalog::query()
            ->where('is_active', true)
            ->findOrFail($data->imagingStudyCatalogId);
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
}
