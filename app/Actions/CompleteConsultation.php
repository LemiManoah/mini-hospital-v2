<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CompleteConsultationDTO;
use App\Enums\ConsultationType;
use App\Models\Consultation;

final readonly class CompleteConsultation
{
    public function __construct(
        private SyncConsultationCharge $syncConsultationCharge,
    ) {}

    public function handle(Consultation $consultation, CompleteConsultationDTO $data): Consultation
    {
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
            'outcome' => $data->outcome,
            'follow_up_instructions' => $data->followUpInstructions,
            'follow_up_days' => $data->followUpDays,
            'is_referred' => $data->isReferred,
            'referred_to_department' => $data->referredToDepartment,
            'referred_to_facility' => $data->referredToFacility,
            'referral_reason' => $data->referralReason,
            'completed_at' => now(),
        ]);

        $this->syncConsultationCharge->handle($consultation->refresh());

        return $consultation->refresh();
    }
}
