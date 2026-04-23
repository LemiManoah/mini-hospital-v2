<?php

declare(strict_types=1);

namespace App\Data\Inventory;

final readonly class IssueInventoryRequisitionItemDTO
{
    /**
     * @param  list<IssueInventoryRequisitionAllocationDTO>  $allocations
     */
    public function __construct(
        public string $inventoryRequisitionItemId,
        public float|int|string $issueQuantity,
        public ?string $notes,
        public array $allocations,
    ) {}

    /**
     * @param  array{
     *   inventory_requisition_item_id: string,
     *   issue_quantity: float|int|string,
     *   notes?: string|null,
     *   allocations?: list<array{inventory_batch_id: string, quantity: float|int|string}>
     * }  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inventoryRequisitionItemId: $payload['inventory_requisition_item_id'],
            issueQuantity: $payload['issue_quantity'],
            notes: self::nullableString($payload['notes'] ?? null),
            allocations: array_map(
                IssueInventoryRequisitionAllocationDTO::fromPayload(...),
                $payload['allocations'] ?? [],
            ),
        );
    }

    /**
     * @return array{
     *   inventory_requisition_item_id: string,
     *   issue_quantity: float|int|string,
     *   notes: ?string,
     *   allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>
     * }
     */
    public function toAttributes(): array
    {
        return [
            'inventory_requisition_item_id' => $this->inventoryRequisitionItemId,
            'issue_quantity' => $this->issueQuantity,
            'notes' => $this->notes,
            'allocations' => array_map(
                static fn (IssueInventoryRequisitionAllocationDTO $allocation): array => $allocation->toAttributes(),
                $this->allocations,
            ),
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
