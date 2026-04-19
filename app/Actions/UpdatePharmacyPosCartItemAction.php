<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PharmacyPosCartItem;
use App\Support\InventoryStockLedger;
use Illuminate\Validation\ValidationException;

final readonly class UpdatePharmacyPosCartItemAction
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(PharmacyPosCartItem $cartItem, array $attributes): PharmacyPosCartItem
    {
        $quantity = max(0.0, round((float) ($attributes['quantity'] ?? $cartItem->quantity), 3));

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be greater than zero.',
            ]);
        }

        $cart = $cartItem->cart;
        $branchId = $cart?->branch_id;
        $locationId = $cart?->inventory_location_id;
        $inventoryItemId = $cartItem->inventory_item_id;

        if (is_string($branchId) && is_string($locationId)) {
            $availableQty = $this->inventoryStockLedger
                ->summarizeByLocation($branchId)
                ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId
                    && $balance['inventory_item_id'] === $inventoryItemId)
                ->sum('quantity');

            if ($availableQty < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Only %.3f units available in the selected location.',
                        (float) $availableQty,
                    ),
                ]);
            }
        }

        $cartItem->update([
            'quantity' => $quantity,
            'unit_price' => max(0.0, round((float) ($attributes['unit_price'] ?? $cartItem->unit_price), 2)),
            'discount_amount' => max(0.0, round((float) ($attributes['discount_amount'] ?? $cartItem->discount_amount), 2)),
            'notes' => $this->nullableText($attributes['notes'] ?? null),
        ]);

        return $cartItem->refresh();
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
