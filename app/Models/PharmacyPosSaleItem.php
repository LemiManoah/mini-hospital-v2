<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacyPosSaleItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PharmacyPosSaleItem extends Model
{
    /** @use HasFactory<PharmacyPosSaleItemFactory> */
    use HasFactory;

    use HasUuids;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosSale::class, 'pharmacy_pos_sale_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PharmacyPosSaleItemAllocation::class);
    }

    protected function casts(): array
    {
        return [
            'pharmacy_pos_sale_id' => 'string',
            'inventory_item_id' => 'string',
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
