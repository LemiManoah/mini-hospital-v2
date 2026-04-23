<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateInventoryRequisitionDTO
{
    /**
     * @param  list<CreateInventoryRequisitionItemDTO>  $items
     */
    public function __construct(
        public string $sourceInventoryLocationId,
        public string $destinationInventoryLocationId,
        public string $priority,
        public string $requisitionDate,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   source_inventory_location_id: string,
         *   destination_inventory_location_id: string,
         *   priority: string,
         *   requisition_date: string,
         *   notes?: string|null,
         *   items: list<array{
         *     inventory_item_id: string,
         *     requested_quantity: float|int|string,
         *     notes?: string|null
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            sourceInventoryLocationId: $validated['source_inventory_location_id'],
            destinationInventoryLocationId: $validated['destination_inventory_location_id'],
            priority: $validated['priority'],
            requisitionDate: $validated['requisition_date'],
            notes: self::nullableString($validated['notes'] ?? null),
            items: array_map(
                CreateInventoryRequisitionItemDTO::fromPayload(...),
                $validated['items'],
            ),
        );
    }

    /**
     * @return array{
     *   source_inventory_location_id: string,
     *   destination_inventory_location_id: string,
     *   priority: string,
     *   requisition_date: string,
     *   notes: ?string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'source_inventory_location_id' => $this->sourceInventoryLocationId,
            'destination_inventory_location_id' => $this->destinationInventoryLocationId,
            'priority' => $this->priority,
            'requisition_date' => $this->requisitionDate,
            'notes' => $this->notes,
        ];
    }

    /**
     * @return list<array{
     *   inventory_item_id: string,
     *   requested_quantity: float|int|string,
     *   notes: ?string
     * }>
     */
    public function itemAttributes(): array
    {
        return array_map(
            static fn (CreateInventoryRequisitionItemDTO $item): array => $item->toAttributes(),
            $this->items,
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
