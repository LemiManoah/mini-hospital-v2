<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Consultation;

final readonly class UpdateConsultation
{
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
}
