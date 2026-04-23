<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateInventoryReconciliationDTO
{
    /**
     * @param  list<CreateInventoryReconciliationItemDTO>  $items
     */
    public function __construct(
        public string $inventoryLocationId,
        public string $reconciliationDate,
        public string $reason,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /** @var array{
         *   inventory_location_id: string,
         *   reconciliation_date: string,
         *   reason: string,
         *   notes?: string|null,
         *   items: list<array{
         *     inventory_item_id: string,
         *     actual_quantity: float|int|string,
         *     unit_cost?: float|int|string|null,
         *     inventory_batch_id?: string|null,
         *     batch_number?: string|null,
         *     expiry_date?: string|null,
         *     notes?: string|null
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            inventoryLocationId: $validated['inventory_location_id'],
            reconciliationDate: $validated['reconciliation_date'],
            reason: $validated['reason'],
            notes: self::nullableString($validated['notes'] ?? null),
            items: array_map(
                CreateInventoryReconciliationItemDTO::fromPayload(...),
                $validated['items'],
            ),
        );
    }

    /**
     * @return array{
     *   inventory_location_id: string,
     *   reconciliation_date: string,
     *   reason: string,
     *   notes: ?string
     * }
     */
    public function toAttributes(): array
    {
        return [
            'inventory_location_id' => $this->inventoryLocationId,
            'reconciliation_date' => $this->reconciliationDate,
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }

    /**
     * @return list<array{
     *   inventory_item_id: string,
     *   actual_quantity: float|int|string,
     *   unit_cost: float|int|string|null,
     *   inventory_batch_id: ?string,
     *   batch_number: ?string,
     *   expiry_date: ?string,
     *   notes: ?string
     * }>
     */
    public function itemAttributes(): array
    {
        return array_map(
            static fn (CreateInventoryReconciliationItemDTO $item): array => $item->toAttributes(),
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
