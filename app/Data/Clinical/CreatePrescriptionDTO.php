<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

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
     * }  $validated
     */
    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
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
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            primaryDiagnosis: self::nullableString($validated['primary_diagnosis'] ?? null),
            pharmacyNotes: self::nullableString($validated['pharmacy_notes'] ?? null),
            isDischargeMedication: $validated['is_discharge_medication'] ?? false,
            isLongTerm: $validated['is_long_term'] ?? false,
            items: array_map(
                CreatePrescriptionItemDTO::fromPayload(...),
                $validated['items'],
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
