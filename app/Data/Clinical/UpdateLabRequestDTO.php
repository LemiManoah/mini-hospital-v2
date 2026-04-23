<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;

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

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   test_ids: list<string>,
         *   clinical_notes?: string|null,
         *   priority?: Priority|string,
         *   diagnosis_code?: string|null,
         *   is_stat?: bool
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            testIds: array_values(array_unique($validated['test_ids'])),
            clinicalNotes: self::nullableString($validated['clinical_notes'] ?? null),
            priority: self::priority($validated['priority'] ?? Priority::ROUTINE),
            diagnosisCode: self::nullableString($validated['diagnosis_code'] ?? null),
            isStat: $validated['is_stat'] ?? false,
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
