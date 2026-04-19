<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VisitStatus;
use App\Models\Consultation;
use App\Models\PatientVisit;
use Illuminate\Support\Facades\Auth;

final readonly class CreateConsultation
{
    public function __construct(
        private TransitionPatientVisitStatus $transitionStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(PatientVisit $visit, array $data): Consultation
    {
        $doctorId = $visit->doctor_id ?? Auth::user()?->staff_id;

        $consultation = Consultation::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'doctor_id' => $doctorId,
            'started_at' => now(),
            'chief_complaint' => $this->chiefComplaint($visit, $data),
            'history_of_present_illness' => $this->nullableText($data['history_of_present_illness'] ?? null),
            'review_of_systems' => $this->nullableText($data['review_of_systems'] ?? null),
            'past_medical_history_summary' => $this->nullableText($data['past_medical_history_summary'] ?? null),
            'family_history' => $this->nullableText($data['family_history'] ?? null),
            'social_history' => $this->nullableText($data['social_history'] ?? null),
            'subjective_notes' => $this->nullableText($data['subjective_notes'] ?? null),
            'objective_findings' => $this->nullableText($data['objective_findings'] ?? null),
            'assessment' => $this->nullableText($data['assessment'] ?? null),
            'plan' => $this->nullableText($data['plan'] ?? null),
            'primary_diagnosis' => $this->nullableText($data['primary_diagnosis'] ?? null),
            'primary_icd10_code' => $this->nullableText($data['primary_icd10_code'] ?? null),
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function chiefComplaint(PatientVisit $visit, array $data): string
    {
        $complaint = $this->nullableText($data['chief_complaint'] ?? null);

        if ($complaint !== null) {
            return $complaint;
        }

        return (string) ($visit->triage->chief_complaint ?? '');
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
