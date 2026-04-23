<?php

declare(strict_types=1);

namespace App\Data\Clinical;

final readonly class StoreLabResultEntryParameterValueDTO
{
    public function __construct(
        public string $labTestResultParameterId,
        public ?string $value,
    ) {}

    /**
     * @param  array{
     *   lab_test_result_parameter_id: string,
     *   value?: string|null
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            labTestResultParameterId: $payload['lab_test_result_parameter_id'],
            value: self::nullableString($payload['value'] ?? null),
        );
    }

    /**
     * @return array{
     *   lab_test_result_parameter_id: string,
     *   value: ?string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'lab_test_result_parameter_id' => $this->labTestResultParameterId,
            'value' => $this->value,
        ];
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
