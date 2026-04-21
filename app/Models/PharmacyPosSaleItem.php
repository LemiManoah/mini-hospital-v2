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
 * @property-read string $pharmacy_pos_sale_id
 * @property-read string $inventory_item_id
 * @property-read string $quantity
 * @property-read string $unit_price
 * @property-read string $discount_amount
 * @property-read string $line_total
 * @property-read string|null $notes
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read PharmacyPosSale|null $sale
 * @property-read InventoryItem|null $inventoryItem
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PharmacyPosSaleItemAllocation> $allocations
 */
final class PharmacyPosSaleItem extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<PharmacyPosSale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosSale::class, 'pharmacy_pos_sale_id');
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return HasMany<PharmacyPosSaleItemAllocation, $this> */
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
