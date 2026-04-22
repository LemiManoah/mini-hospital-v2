<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class CreateGoodsReceiptItemDTO
{
    public function __construct(
        public string $purchaseOrderItemId,
        public string $inventoryItemId,
        public float $quantityReceived,
        public float $unitCost,
        public ?string $batchNumber,
        public ?string $expiryDate,
        public ?string $notes,
    ) {}

    /**
     * @param  array{
     *   purchase_order_item_id: string,
     *   inventory_item_id: string,
     *   quantity_received: int|float|string,
     *   unit_cost: int|float|string,
     *   batch_number?: string|null,
     *   expiry_date?: string|null,
     *   notes?: string|null
     * }  $attributes
     */
    public static function fromRequest(array $attributes): self
    {
        return new self(
            purchaseOrderItemId: $attributes['purchase_order_item_id'],
            inventoryItemId: $attributes['inventory_item_id'],
            quantityReceived: (float) $attributes['quantity_received'],
            unitCost: (float) $attributes['unit_cost'],
            batchNumber: self::nullableString($attributes['batch_number'] ?? null),
            expiryDate: self::nullableString($attributes['expiry_date'] ?? null),
            notes: self::nullableString($attributes['notes'] ?? null),
        );
    }

    public function hasPositiveQuantity(): bool
    {
        return $this->quantityReceived > 0;
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
