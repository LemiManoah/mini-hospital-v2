<?php

declare(strict_types=1);

namespace App\Data\Clinical;

final readonly class CreatePrescriptionDTO
{
    /**
     * @param  list<CreatePrescriptionItemDTO>  $items
     */
    public function __construct(
        public ?string $primaryDiagnosis,
        public ?string $pharmacyNotes,
        public bool $isDischargeMedication,
        public bool $isLongTerm,
        public array $items,
    ) {}

    /**
     * @param  array{
     *   primary_diagnosis?: string|null,
     *   pharmacy_notes?: string|null,
     *   is_discharge_medication?: bool,
     *   is_long_term?: bool,
     *   items: list<array{
     *     inventory_item_id: string,
     *     dosage: string,
     *     frequency: string,
     *     route: string,
     *     duration_days: int,
     *     quantity: int,
     *     instructions?: string|null,
     *     is_prn?: bool,
     *     prn_reason?: string|null,
     *     is_external_pharmacy?: bool
     *   }>
     * }  $attributes
     */
    public static function fromRequest(array $attributes): self
    {
        return new self(
            primaryDiagnosis: self::nullableString($attributes['primary_diagnosis'] ?? null),
            pharmacyNotes: self::nullableString($attributes['pharmacy_notes'] ?? null),
            isDischargeMedication: $attributes['is_discharge_medication'] ?? false,
            isLongTerm: $attributes['is_long_term'] ?? false,
            items: array_map(
                static fn (array $item): CreatePrescriptionItemDTO => CreatePrescriptionItemDTO::fromRequest($item),
                $attributes['items'],
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
