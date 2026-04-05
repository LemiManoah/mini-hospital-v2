<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockMovement extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'inventory_location_id' => 'string',
        'inventory_item_id' => 'string',
        'inventory_batch_id' => 'string',
        'movement_type' => StockMovementType::class,
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'source_document_id' => 'string',
        'source_line_id' => 'string',
        'occurred_at' => 'datetime',
        'created_by' => 'string',
    ];

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
