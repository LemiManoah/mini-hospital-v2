<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $pharmacy_pos_cart_id
 * @property-read string $inventory_item_id
 * @property-read string $quantity
 * @property-read string $unit_price
 * @property-read string $discount_amount
 * @property-read string|null $notes
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read PharmacyPosCart|null $cart
 * @property-read InventoryItem|null $inventoryItem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PharmacyPosCartItemAllocation> $allocations
 */
final class PharmacyPosCartItem extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<PharmacyPosCart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosCart::class, 'pharmacy_pos_cart_id');
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return HasMany<PharmacyPosCartItemAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PharmacyPosCartItemAllocation::class);
    }

    public function lineTotal(): float
    {
        $gross = round((float) $this->quantity * (float) $this->unit_price, 2);

        return max(0.0, round($gross - (float) $this->discount_amount, 2));
    }

    protected function casts(): array
    {
        return [
            'pharmacy_pos_cart_id' => 'string',
            'inventory_item_id' => 'string',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }
}
