<?php

declare(strict_types=1);

namespace App\Data\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final readonly class CreateGoodsReceiptDTO
{
    /**
     * @param  list<CreateGoodsReceiptItemDTO>  $items
     * @param  list<string>  $allowedLocationTypes
     */
    public function __construct(
        public string $purchaseOrderId,
        public string $inventoryLocationId,
        public string $receiptDate,
        public ?string $supplierInvoiceNumber,
        public ?string $notes,
        public array $items,
        public array $allowedLocationTypes = [],
    ) {}

    /**
     * @param  list<string>  $allowedLocationTypes
     */
    public static function fromRequest(FormRequest $request, array $allowedLocationTypes = []): self
    {
        /** @var array{
         *   purchase_order_id: string,
         *   inventory_location_id: string,
         *   receipt_date: string,
         *   supplier_invoice_number?: string|null,
         *   notes?: string|null,
         *   items: list<array{
         *     purchase_order_item_id: string,
         *     inventory_item_id: string,
         *     quantity_received: int|float|string,
         *     unit_cost: int|float|string,
         *     batch_number?: string|null,
         *     expiry_date?: string|null,
         *     notes?: string|null
         *   }>
         * } $validated
         */
        $validated = $request->validated();

        return new self(
            purchaseOrderId: $validated['purchase_order_id'],
            inventoryLocationId: $validated['inventory_location_id'],
            receiptDate: $validated['receipt_date'],
            supplierInvoiceNumber: self::nullableString($validated['supplier_invoice_number'] ?? null),
            notes: self::nullableString($validated['notes'] ?? null),
            items: array_map(
                CreateGoodsReceiptItemDTO::fromPayload(...),
                $validated['items'],
            ),
            allowedLocationTypes: $allowedLocationTypes,
        );
    }

    /**
     * @return list<CreateGoodsReceiptItemDTO>
     */
    public function receiptItems(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (CreateGoodsReceiptItemDTO $item): bool => $item->hasPositiveQuantity(),
        ));
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
