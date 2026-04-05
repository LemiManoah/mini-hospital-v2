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

    /** @use HasFactory<\Database\Factories\InventoryBatchFactory> */
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }
}
