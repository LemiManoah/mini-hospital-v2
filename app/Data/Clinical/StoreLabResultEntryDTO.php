<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class StoreLabResultEntryDTO
{
    /**
     * @param  list<StoreLabResultEntryParameterValueDTO>  $parameterValues
     */
    public function __construct(
        public ?string $resultNotes,
        public ?string $freeEntryValue,
        public ?string $selectedOptionLabel,
        public array $parameterValues,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   result_notes?: string|null,
         *   free_entry_value?: string|null,
         *   selected_option_label?: string|null,
         *   parameter_values?: list<array{
         *     lab_test_result_parameter_id: string,
         *     value?: string|null
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            resultNotes: self::nullableString($validated['result_notes'] ?? null),
            freeEntryValue: self::nullableString($validated['free_entry_value'] ?? null),
            selectedOptionLabel: self::nullableString($validated['selected_option_label'] ?? null),
            parameterValues: array_map(
                StoreLabResultEntryParameterValueDTO::fromPayload(...),
                $validated['parameter_values'] ?? [],
            ),
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
