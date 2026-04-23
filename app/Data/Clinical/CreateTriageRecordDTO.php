<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateTriageRecordDTO
{
    public function __construct(
        public string $triageGrade,
        public string $attendanceType,
        public ?int $newsScore,
        public ?int $pewsScore,
        public string $consciousLevel,
        public string $mobilityStatus,
        public string $chiefComplaint,
        public ?string $historyOfPresentingIllness,
        public ?string $assignedClinicId,
        public bool $requiresPriority,
        public bool $isPediatric,
        public bool $poisoningCase,
        public ?string $poisoningAgent,
        public bool $snakeBiteCase,
        public ?string $referredBy,
        public ?string $nurseNotes,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   triage_grade: string,
         *   attendance_type: string,
         *   news_score?: int|null,
         *   pews_score?: int|null,
         *   conscious_level: string,
         *   mobility_status: string,
         *   chief_complaint: string,
         *   history_of_presenting_illness?: string|null,
         *   assigned_clinic_id?: string|null,
         *   requires_priority?: bool,
         *   is_pediatric?: bool,
         *   poisoning_case?: bool,
         *   poisoning_agent?: string|null,
         *   snake_bite_case?: bool,
         *   referred_by?: string|null,
         *   nurse_notes?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            triageGrade: $validated['triage_grade'],
            attendanceType: $validated['attendance_type'],
            newsScore: $validated['news_score'] ?? null,
            pewsScore: $validated['pews_score'] ?? null,
            consciousLevel: $validated['conscious_level'],
            mobilityStatus: $validated['mobility_status'],
            chiefComplaint: $validated['chief_complaint'],
            historyOfPresentingIllness: self::nullableString($validated['history_of_presenting_illness'] ?? null),
            assignedClinicId: self::nullableString($validated['assigned_clinic_id'] ?? null),
            requiresPriority: $validated['requires_priority'] ?? false,
            isPediatric: $validated['is_pediatric'] ?? false,
            poisoningCase: $validated['poisoning_case'] ?? false,
            poisoningAgent: self::nullableString($validated['poisoning_agent'] ?? null),
            snakeBiteCase: $validated['snake_bite_case'] ?? false,
            referredBy: self::nullableString($validated['referred_by'] ?? null),
            nurseNotes: self::nullableString($validated['nurse_notes'] ?? null),
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
