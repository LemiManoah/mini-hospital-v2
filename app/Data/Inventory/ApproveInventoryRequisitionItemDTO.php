<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class ApproveInventoryRequisitionItemDTO
{
    public function __construct(
        public string $inventoryRequisitionItemId,
        public int|float|null $approvedQuantity,
    ) {}

    /**
     * @param  array{
     *   inventory_requisition_item_id: string,
     *   approved_quantity: int|float|numeric-string|null
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryRequisitionItemId: $payload['inventory_requisition_item_id'],
            approvedQuantity: is_numeric($payload['approved_quantity']) ? (float) $payload['approved_quantity'] : null,
        );
    }

    /**
     * @return array{
     *   inventory_requisition_item_id: string,
     *   approved_quantity: int|float|null
     * }
     */
    public function toAttributes(): array
    {
        return [
            'inventory_requisition_item_id' => $this->inventoryRequisitionItemId,
            'approved_quantity' => $this->approvedQuantity,
        ];
    }
}
