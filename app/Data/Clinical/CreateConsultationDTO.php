<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateConsultationDTO
{
    public function __construct(
        public ?string $chiefComplaint,
        public ?string $historyOfPresentIllness,
        public ?string $reviewOfSystems,
        public ?string $pastMedicalHistorySummary,
        public ?string $familyHistory,
        public ?string $socialHistory,
        public ?string $subjectiveNotes,
        public ?string $objectiveFindings,
        public ?string $assessment,
        public ?string $plan,
        public ?string $primaryDiagnosis,
        public ?string $primaryIcd10Code,
    ) {}

    /**
     * @param  array{
     *   chief_complaint?: string|null,
     *   history_of_present_illness?: string|null,
     *   review_of_systems?: string|null,
     *   past_medical_history_summary?: string|null,
     *   family_history?: string|null,
     *   social_history?: string|null,
     *   subjective_notes?: string|null,
     *   objective_findings?: string|null,
     *   assessment?: string|null,
     *   plan?: string|null,
     *   primary_diagnosis?: string|null,
     *   primary_icd10_code?: string|null
     * } $validated
     */
    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   chief_complaint?: string|null,
         *   history_of_present_illness?: string|null,
         *   review_of_systems?: string|null,
         *   past_medical_history_summary?: string|null,
         *   family_history?: string|null,
         *   social_history?: string|null,
         *   subjective_notes?: string|null,
         *   objective_findings?: string|null,
         *   assessment?: string|null,
         *   plan?: string|null,
         *   primary_diagnosis?: string|null,
         *   primary_icd10_code?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            chiefComplaint: self::nullableString($validated['chief_complaint'] ?? null),
            historyOfPresentIllness: self::nullableString($validated['history_of_present_illness'] ?? null),
            reviewOfSystems: self::nullableString($validated['review_of_systems'] ?? null),
            pastMedicalHistorySummary: self::nullableString($validated['past_medical_history_summary'] ?? null),
            familyHistory: self::nullableString($validated['family_history'] ?? null),
            socialHistory: self::nullableString($validated['social_history'] ?? null),
            subjectiveNotes: self::nullableString($validated['subjective_notes'] ?? null),
            objectiveFindings: self::nullableString($validated['objective_findings'] ?? null),
            assessment: self::nullableString($validated['assessment'] ?? null),
            plan: self::nullableString($validated['plan'] ?? null),
            primaryDiagnosis: self::nullableString($validated['primary_diagnosis'] ?? null),
            primaryIcd10Code: self::nullableString($validated['primary_icd10_code'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
