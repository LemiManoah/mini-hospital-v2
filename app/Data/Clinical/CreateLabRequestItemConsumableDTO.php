<?php

declare(strict_types=1);

namespace App\Data\Clinical;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateLabRequestItemConsumableDTO
{
    public function __construct(
        public ?string $inventoryItemId,
        public string $consumableName,
        public ?string $unitLabel,
        public float $quantity,
        public float $unitCost,
        public ?string $notes,
        public ?string $usedAt,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     inventory_item_id?: string|null,
         *     consumable_name: string,
         *     unit_label?: string|null,
         *     quantity: int|float|string,
         *     unit_cost: int|float|string,
         *     notes?: string|null,
         *     used_at?: string|null
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            inventoryItemId: self::nullableText($validated['inventory_item_id'] ?? null),
            consumableName: $validated['consumable_name'],
            unitLabel: self::nullableText($validated['unit_label'] ?? null),
            quantity: self::numericValue($validated['quantity']),
            unitCost: self::numericValue($validated['unit_cost']),
            notes: self::nullableText($validated['notes'] ?? null),
            usedAt: self::nullableText($validated['used_at'] ?? null),
        );
    }

    private static function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function numericValue(int|float|string $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $trimmed = mb_trim($value);

        return is_numeric($trimmed) ? (float) $trimmed : 0.0;
    }
}
