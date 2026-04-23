<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class UpdatePurchaseOrderDTO
{
    /**
     * @param  list<PurchaseOrderItemDTO>  $items
     */
    public function __construct(
        public string $supplierId,
        public string $orderDate,
        public ?string $expectedDeliveryDate,
        public ?string $notes,
        public array $items,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        /**
         * @var array{
         *     supplier_id: string,
         *     order_date: string,
         *     expected_delivery_date?: string|null,
         *     notes?: string|null,
         *     items: list<array{
         *         inventory_item_id: string,
         *         quantity_ordered: int|float|string,
         *         unit_cost: int|float|string
         *     }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            supplierId: $validated['supplier_id'],
            orderDate: $validated['order_date'],
            expectedDeliveryDate: self::nullableString($validated['expected_delivery_date'] ?? null),
            notes: self::nullableString($validated['notes'] ?? null),
            items: array_map(
                PurchaseOrderItemDTO::fromPayload(...),
                $validated['items'],
            ),
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
