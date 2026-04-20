<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacyPosCartItemAllocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PharmacyPosCartItemAllocation extends Model
{
    /** @use HasFactory<PharmacyPosCartItemAllocationFactory> */
    use HasFactory;

    use HasUuids;

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosCartItem::class, 'pharmacy_pos_cart_item_id');
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    protected function casts(): array
    {
        return [
            'pharmacy_pos_cart_item_id' => 'string',
            'inventory_batch_id' => 'string',
            'quantity' => 'decimal:3',
        ];
    }
}
