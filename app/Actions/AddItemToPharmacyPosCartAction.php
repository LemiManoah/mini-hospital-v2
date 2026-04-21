<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AddItemToPharmacyPosCartAction
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    /**
     * @param  array{
     *   inventory_item_id?: string,
     *   quantity?: int|float|string,
     *   unit_price?: int|float|string|null,
     *   discount_amount?: int|float|string|null,
     *   notes?: string|null
     * }  $attributes
     */
    public function handle(PharmacyPosCart $cart, array $attributes): PharmacyPosCartItem
    {
        return DB::transaction(function () use ($cart, $attributes): PharmacyPosCartItem {
            $inventoryItemId = is_string($attributes['inventory_item_id'] ?? null)
                ? $attributes['inventory_item_id']
                : null;

            if ($inventoryItemId === null) {
                throw ValidationException::withMessages([
                    'inventory_item_id' => 'Select a valid inventory item.',
                ]);
            }

            $inventoryItem = InventoryItem::query()
                ->where('is_active', true)
                ->findOrFail($inventoryItemId);

            $quantity = max(0.0, round($this->toFloat($attributes['quantity'] ?? 1), 3));

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            $unitPrice = max(0.0, round($this->toFloat($attributes['unit_price'] ?? $inventoryItem->default_selling_price ?? 0), 2));
            $discountAmount = max(0.0, round($this->toFloat($attributes['discount_amount'] ?? 0), 2));
            $availableQty = $this->inventoryStockLedger
                ->summarizeByLocation($cart->branch_id)
                ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $cart->inventory_location_id
                    && $balance['inventory_item_id'] === $inventoryItemId)
                ->reduce(
                    static fn (float $carry, array $balance): float => $carry + (float) $balance['quantity'],
                    0.0,
                );

            if ($availableQty < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Only %.3f units available in the selected location.',
                        (float) $availableQty,
                    ),
                ]);
            }

            $existing = $cart->items()
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            if ($existing instanceof PharmacyPosCartItem) {
                $existing->update([
                    'quantity' => round((float) $existing->quantity + $quantity, 3),
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'notes' => $this->nullableText($attributes['notes'] ?? null),
                ]);

                return $existing->refresh();
            }

            /** @var PharmacyPosCartItem $createdItem */
            $createdItem = $cart->items()->create([
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'notes' => $this->nullableText($attributes['notes'] ?? null),
            ]);

            return $createdItem;
        });
    }

    private function toFloat(int|float|string|null $value): float
    {
        return (float) ($value ?? 0);
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
