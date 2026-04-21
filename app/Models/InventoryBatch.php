<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryBatch extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'inventory_location_id' => 'string',
        'inventory_item_id' => 'string',
        'goods_receipt_item_id' => 'string',
        'unit_cost' => 'decimal:2',
        'quantity_received' => 'decimal:3',
        'expiry_date' => 'date',
        'received_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** @return BelongsTo<InventoryLocation, $this> */
    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    /** @return BelongsTo<GoodsReceiptItem, $this> */
    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasMany<DispensingRecordItemAllocation, $this> */
    public function dispensingAllocations(): HasMany
    {
        return $this->hasMany(DispensingRecordItemAllocation::class);
    }

    /** @return HasMany<ReconciliationItem, $this> */
    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }
}
