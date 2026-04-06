<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'purchase_order_id' => 'string',
        'inventory_item_id' => 'string',
        'quantity_ordered' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quantity_received' => 'decimal:3',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->quantity_received >= (float) $this->quantity_ordered;
    }

    public function remainingQuantity(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->quantity_received);
    }
}
