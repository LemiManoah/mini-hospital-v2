<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class GoodsReceipt extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<GoodsReceiptFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'purchase_order_id' => 'string',
        'inventory_location_id' => 'string',
        'status' => GoodsReceiptStatus::class,
        'receipt_date' => 'date',
        'created_by' => 'string',
        'updated_by' => 'string',
        'posted_by' => 'string',
        'posted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    /**
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
