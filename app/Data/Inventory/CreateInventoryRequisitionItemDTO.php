<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class CreateInventoryRequisitionItemDTO
{
    public function __construct(
        public string $inventoryItemId,
        public int|float|string $requestedQuantity,
        public ?string $notes,
    ) {}

    /**
     * @param  array{
     *   inventory_item_id: string,
     *   requested_quantity: float|int|string,
     *   notes?: string|null
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryItemId: $payload['inventory_item_id'],
            requestedQuantity: $payload['requested_quantity'],
            notes: self::nullableString($payload['notes'] ?? null),
        );
    }

    /**
     * @return array{
     *   inventory_item_id: string,
     *   requested_quantity: float|int|string,
     *   notes: ?string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'inventory_item_id' => $this->inventoryItemId,
            'requested_quantity' => $this->requestedQuantity,
            'notes' => $this->notes,
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
