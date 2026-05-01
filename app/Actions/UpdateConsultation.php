<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\UpdateConsultationDTO;
use App\Enums\ConsultationType;
use App\Models\Consultation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateConsultation
{
    public function __construct(
        private SyncConsultationCharge $syncConsultationCharge,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(Consultation $consultation, UpdateConsultationDTO $data): Consultation
    {
        $oldValues = [
            'consultation_type' => $consultation->consultation_type?->value,
            'chief_complaint' => $consultation->chief_complaint,
            'assessment' => $consultation->assessment,
            'plan' => $consultation->plan,
            'primary_diagnosis' => $consultation->primary_diagnosis,
            'primary_icd10_code' => $consultation->primary_icd10_code,
        ];

        $consultation->update([
            'consultation_type' => $data->consultationType
                ?? $consultation->consultation_type
                ?? ConsultationType::defaultForVisit($consultation->visit()->firstOrFail()),
            'chief_complaint' => $data->chiefComplaint,
            'history_of_present_illness' => $data->historyOfPresentIllness,
            'review_of_systems' => $data->reviewOfSystems,
            'past_medical_history_summary' => $data->pastMedicalHistorySummary,
            'family_history' => $data->familyHistory,
            'social_history' => $data->socialHistory,
            'subjective_notes' => $data->subjectiveNotes,
            'objective_findings' => $data->objectiveFindings,
            'assessment' => $data->assessment,
            'plan' => $data->plan,
            'primary_diagnosis' => $data->primaryDiagnosis,
            'primary_icd10_code' => $data->primaryIcd10Code,
        ]);

        $this->syncConsultationCharge->handle($consultation->refresh());

        $consultation = $consultation->refresh();
        $user = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'clinical',
            event: 'consultation.updated',
            subject: $consultation,
            description: 'Consultation updated.',
            tenantId: $consultation->tenant_id,
            branchId: $consultation->facility_branch_id,
            staffId: $user instanceof User ? $user->staffId() : null,
            oldValues: $oldValues,
            newValues: [
                'consultation_type' => $consultation->consultation_type?->value,
                'chief_complaint' => $consultation->chief_complaint,
                'assessment' => $consultation->assessment,
                'plan' => $consultation->plan,
                'primary_diagnosis' => $consultation->primary_diagnosis,
                'primary_icd10_code' => $consultation->primary_icd10_code,
            ],
        );

        return $consultation;
    }
}
