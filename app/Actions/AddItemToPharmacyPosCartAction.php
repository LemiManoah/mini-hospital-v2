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
     * @param  array<string, mixed>  $attributes
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

            $quantity = max(0.0, round((float) ($attributes['quantity'] ?? 1), 3));

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be greater than zero.',
                ]);
            }

            $unitPrice = max(0.0, round((float) ($attributes['unit_price'] ?? $inventoryItem->default_selling_price ?? 0), 2));
            $discountAmount = max(0.0, round((float) ($attributes['discount_amount'] ?? 0), 2));
            $branchId = $cart->branch_id;
            $locationId = $cart->inventory_location_id;

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

            return $cart->items()->create([
                'inventory_item_id' => $inventoryItemId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'notes' => $this->nullableText($attributes['notes'] ?? null),
            ]);
        });
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
