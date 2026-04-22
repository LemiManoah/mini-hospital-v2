<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use App\Enums\Priority;

final readonly class UpdateLabRequestDTO
{
    /**
     * @param  list<string>  $testIds
     */
    public function __construct(
        public array $testIds,
        public ?string $clinicalNotes,
        public Priority $priority,
        public ?string $diagnosisCode,
        public bool $isStat,
    ) {}

    /**
     * @param  array{
     *   test_ids: list<string>,
     *   clinical_notes?: string|null,
     *   priority?: Priority|string,
     *   diagnosis_code?: string|null,
     *   is_stat?: bool
     * }  $attributes
     */
    public static function fromRequest(array $attributes): self
    {
        return new self(
            testIds: array_values(array_unique($attributes['test_ids'])),
            clinicalNotes: self::nullableString($attributes['clinical_notes'] ?? null),
            priority: self::priority($attributes['priority'] ?? Priority::ROUTINE),
            diagnosisCode: self::nullableString($attributes['diagnosis_code'] ?? null),
            isStat: $attributes['is_stat'] ?? false,
        );
    }

    private static function priority(Priority|string $priority): Priority
    {
        return $priority instanceof Priority
            ? $priority
            : Priority::from($priority);
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
