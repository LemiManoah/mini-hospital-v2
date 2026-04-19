<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Consultation;

final readonly class CompleteConsultation
{
    /**
     * @param  array{
     *     chief_complaint?: mixed,
     *     history_of_present_illness?: mixed,
     *     review_of_systems?: mixed,
     *     past_medical_history_summary?: mixed,
     *     family_history?: mixed,
     *     social_history?: mixed,
     *     subjective_notes?: mixed,
     *     objective_findings?: mixed,
     *     assessment?: mixed,
     *     plan?: mixed,
     *     primary_diagnosis?: mixed,
     *     primary_icd10_code?: mixed,
     *     outcome?: mixed,
     *     follow_up_instructions?: mixed,
     *     follow_up_days?: mixed,
     *     is_referred?: mixed,
     *     referred_to_department?: mixed,
     *     referred_to_facility?: mixed,
     *     referral_reason?: mixed
     * }  $data
     */
    public function handle(Consultation $consultation, array $data): Consultation
    {
        $consultation->update([
            'chief_complaint' => $this->nullableText($data['chief_complaint'] ?? null),
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
            'outcome' => $this->nullableText($data['outcome'] ?? null),
            'follow_up_instructions' => $this->nullableText($data['follow_up_instructions'] ?? null),
            'follow_up_days' => $this->nullableInt($data['follow_up_days'] ?? null),
            'is_referred' => ! empty($data['is_referred']),
            'referred_to_department' => $this->nullableText($data['referred_to_department'] ?? null),
            'referred_to_facility' => $this->nullableText($data['referred_to_facility'] ?? null),
            'referral_reason' => $this->nullableText($data['referral_reason'] ?? null),
            'completed_at' => now(),
        ]);

        return $consultation->refresh();
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
