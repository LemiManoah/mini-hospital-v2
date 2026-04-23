<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class IssueInventoryRequisitionAllocationDTO
{
    public function __construct(
        public string $inventoryBatchId,
        public float|int|string $quantity,
    ) {}

    /**
     * @param  array{
     *   inventory_batch_id: string,
     *   quantity: float|int|string
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryBatchId: $payload['inventory_batch_id'],
            quantity: $payload['quantity'],
        );
    }

    /**
     * @return array{
     *   inventory_batch_id: string,
     *   quantity: float|int|string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'inventory_batch_id' => $this->inventoryBatchId,
            'quantity' => $this->quantity,
        ];
    }
}
