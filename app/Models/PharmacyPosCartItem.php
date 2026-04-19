<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PharmacyPosCartItem extends Model
{
    /** @use HasFactory<\Database\Factories\PharmacyPosCartItemFactory> */
    use HasFactory;

    use HasUuids;

    public function cart(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosCart::class, 'pharmacy_pos_cart_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

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
