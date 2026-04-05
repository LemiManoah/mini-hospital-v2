<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockAdjustmentItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockAdjustmentItemFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'stock_adjustment_id' => 'string',
        'inventory_item_id' => 'string',
        'inventory_batch_id' => 'string',
        'expected_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'quantity_delta' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
