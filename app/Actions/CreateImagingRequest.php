<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateImagingRequestDTO;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\ImagingRequest;
use App\Models\PatientVisit;
use App\Notifications\ImagingRequestCreatedNotification;

final readonly class CreateImagingRequest
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
        private RecordAuditActivity $recordAuditActivity,
        private NotifyUsersWithPermission $notifyUsersWithPermission,
    ) {}

    public function handle(Consultation|PatientVisit $context, CreateImagingRequestDTO $data, string $staffId): ImagingRequest
    {
        [$visit, $consultation] = $this->resolveContext($context);

        $request = ImagingRequest::query()->create([
            'visit_id' => $visit->id,
            'consultation_id' => $consultation?->id,
            'requested_by' => $staffId,
            'modality' => $data->modality,
            'body_part' => $data->bodyPart,
            'laterality' => $data->laterality,
            'clinical_history' => $data->clinicalHistory,
            'indication' => $data->indication,
            'priority' => $data->priority,
            'status' => 'requested',
            'requires_contrast' => $data->requiresContrast,
            'contrast_allergy_status' => $data->contrastAllergyStatus,
            'pregnancy_status' => $data->pregnancyStatus,
        ])->loadMissing('requestedBy:id,first_name,last_name');

        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }

        $this->recordAuditActivity->handle(
            logName: 'clinical',
            event: 'imaging_request.created',
            subject: $request,
            description: 'Imaging request created.',
            tenantId: $visit->tenant_id,
            branchId: $visit->facility_branch_id,
            staffId: $staffId,
            newValues: [
                'visit_id' => $request->visit_id,
                'consultation_id' => $request->consultation_id,
                'modality' => $request->modality->value,
                'body_part' => $request->body_part,
                'priority' => $request->priority->value,
            ],
        );

        if ($visit->tenant_id !== null) {
            $this->notifyUsersWithPermission->handle(
                $visit->tenant_id,
                ['lab_requests.view', 'lab_requests.update'],
                new ImagingRequestCreatedNotification($request),
            );
        }

        return $request;
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
