<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class CreateInventoryReconciliationItemDTO
{
    public function __construct(
        public string $inventoryItemId,
        public int|float|string $actualQuantity,
        public int|float|string|null $unitCost,
        public ?string $inventoryBatchId,
        public ?string $batchNumber,
        public ?string $expiryDate,
        public ?string $notes,
    ) {}

    /**
     * @param  array{
     *   inventory_item_id: string,
     *   actual_quantity: float|int|string,
     *   unit_cost?: float|int|string|null,
     *   inventory_batch_id?: string|null,
     *   batch_number?: string|null,
     *   expiry_date?: string|null,
     *   notes?: string|null
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryItemId: $payload['inventory_item_id'],
            actualQuantity: $payload['actual_quantity'],
            unitCost: self::nullableNumeric($payload['unit_cost'] ?? null),
            inventoryBatchId: self::nullableString($payload['inventory_batch_id'] ?? null),
            batchNumber: self::nullableString($payload['batch_number'] ?? null),
            expiryDate: self::nullableString($payload['expiry_date'] ?? null),
            notes: self::nullableString($payload['notes'] ?? null),
        );
    }

    /**
     * @return array{
     *   inventory_item_id: string,
     *   actual_quantity: float|int|string,
     *   unit_cost: float|int|string|null,
     *   inventory_batch_id: ?string,
     *   batch_number: ?string,
     *   expiry_date: ?string,
     *   notes: ?string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'inventory_item_id' => $this->inventoryItemId,
            'actual_quantity' => $this->actualQuantity,
            'unit_cost' => $this->unitCost,
            'inventory_batch_id' => $this->inventoryBatchId,
            'batch_number' => $this->batchNumber,
            'expiry_date' => $this->expiryDate,
            'notes' => $this->notes,
        ];
    }

    private static function nullableNumeric(mixed $value): int|float|string|null
    {
        return is_numeric($value) ? $value : null;
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
