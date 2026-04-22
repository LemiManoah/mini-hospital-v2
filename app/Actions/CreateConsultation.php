<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateConsultationDTO;
use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\Auth;

final readonly class CreateConsultation
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    public function handle(PatientVisit $visit, CreateConsultationDTO $data): Consultation
    {
        $doctorId = $visit->doctor_id ?? Auth::user()?->staff_id;

        $consultation = Consultation::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'doctor_id' => $doctorId,
            'started_at' => now(),
            'chief_complaint' => $this->chiefComplaint($visit, $data),
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
            'is_referred' => false,
        ]);

        if ($visit->doctor_id === null && is_string($doctorId) && $doctorId !== '') {
            $visit->update(['doctor_id' => $doctorId]);
        }

        if ($visit->status === VisitStatus::REGISTERED) {
            $this->transitionStatus->handle($visit, VisitStatus::IN_PROGRESS);
        }

        return $consultation;
    }

    private function chiefComplaint(PatientVisit $visit, CreateConsultationDTO $data): string
    {
        $complaint = $data->chiefComplaint;

        if ($complaint !== null) {
            return $complaint;
        }

        /** @var string|null $chiefComplaint */
        $chiefComplaint = $visit->triage?->getAttribute('chief_complaint');

        return $chiefComplaint ?? '';
    }
}
