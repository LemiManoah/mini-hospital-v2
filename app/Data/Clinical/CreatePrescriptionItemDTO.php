<?php

declare(strict_types=1);

namespace App\Data\Clinical;

final readonly class CreatePrescriptionItemDTO
{
    public function __construct(
        public string $inventoryItemId,
        public string $dosage,
        public string $frequency,
        public string $route,
        public int $durationDays,
        public int $quantity,
        public ?string $instructions,
        public bool $isPrn,
        public ?string $prnReason,
        public bool $isExternalPharmacy,
    ) {}

    /**
     * @param  array{
     *   inventory_item_id: string,
     *   dosage: string,
     *   frequency: string,
     *   route: string,
     *   duration_days: int,
     *   quantity: int,
     *   instructions?: string|null,
     *   is_prn?: bool,
     *   prn_reason?: string|null,
     *   is_external_pharmacy?: bool
     * }  $attributes
     */
    public static function fromPayload(array $attributes): self
    {
        return new self(
            inventoryItemId: $attributes['inventory_item_id'],
            dosage: $attributes['dosage'],
            frequency: $attributes['frequency'],
            route: $attributes['route'],
            durationDays: $attributes['duration_days'],
            quantity: $attributes['quantity'],
            instructions: self::nullableString($attributes['instructions'] ?? null),
            isPrn: $attributes['is_prn'] ?? false,
            prnReason: self::nullableString($attributes['prn_reason'] ?? null),
            isExternalPharmacy: $attributes['is_external_pharmacy'] ?? false,
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
