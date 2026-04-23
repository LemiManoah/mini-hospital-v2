<?php

declare(strict_types=1);

namespace App\Data\Clinical;

final readonly class LabTestCatalogResultParameterDTO
{
    public function __construct(
        public string $label,
        public ?string $unit,
        public ?string $referenceRange,
        public string $valueType,
    ) {}

    /**
     * @param  array{
     *      label: string,
     *      unit?: string|null,
     *      reference_range?: string|null,
     *      value_type?: string|null
     *  }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $valueType = $payload['value_type'] ?? 'numeric';

        return new self(
            label: $payload['label'],
            unit: self::nullableString($payload['unit'] ?? null),
            referenceRange: self::nullableString($payload['reference_range'] ?? null),
            valueType: $valueType === 'text' ? 'text' : 'numeric',
        );
    }

    /**
     * @return array{
     *      label: string,
     *      unit: ?string,
     *      gender: null,
     *      age_min: null,
     *      age_max: null,
     *      reference_range: ?string,
     *      value_type: string,
     *      sort_order: int,
     *      is_active: bool
     *  }
     */
    public function toRecordPayload(int $sortOrder): array
    {
        return [
            'label' => $this->label,
            'unit' => $this->unit,
            'gender' => null,
            'age_min' => null,
            'age_max' => null,
            'reference_range' => $this->referenceRange,
            'value_type' => $this->valueType,
            'sort_order' => $sortOrder,
            'is_active' => true,
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
