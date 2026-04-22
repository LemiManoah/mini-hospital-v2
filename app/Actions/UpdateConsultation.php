<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\UpdateConsultationDTO;
use App\Models\Consultation;

final readonly class UpdateConsultation
{
    public function handle(Consultation $consultation, UpdateConsultationDTO $data): Consultation
    {
        $consultation->update([
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

        return $consultation->refresh();
    }
}
