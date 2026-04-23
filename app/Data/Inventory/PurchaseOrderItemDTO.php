<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class PurchaseOrderItemDTO
{
    public function __construct(
        public string $inventoryItemId,
        public float $quantityOrdered,
        public float $unitCost,
    ) {}

    /**
     * @param  array{inventory_item_id: string, quantity_ordered: int|float|string, unit_cost: int|float|string}  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryItemId: $payload['inventory_item_id'],
            quantityOrdered: self::numericValue($payload['quantity_ordered']),
            unitCost: self::numericValue($payload['unit_cost']),
        );
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
