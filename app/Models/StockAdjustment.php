<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockAdjustmentStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StockAdjustment extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\StockAdjustmentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'inventory_location_id' => 'string',
        'status' => StockAdjustmentStatus::class,
        'adjustment_date' => 'date',
        'created_by' => 'string',
        'updated_by' => 'string',
        'posted_by' => 'string',
        'posted_at' => 'datetime',
    ];

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
