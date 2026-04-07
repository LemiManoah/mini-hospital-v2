<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryLocationType;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Database\Factories\InventoryLocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InventoryLocation extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<InventoryLocationFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'type' => InventoryLocationType::class,
        'is_dispensing_point' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function locationItems(): HasMany
    {
        return $this->hasMany(InventoryLocationItem::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    public function fulfillingRequisitions(): HasMany
    {
        return $this->hasMany(InventoryRequisition::class, 'source_inventory_location_id');
    }

    public function sourceRequisitions(): HasMany
    {
        return $this->fulfillingRequisitions();
    }

    public function requestingRequisitions(): HasMany
    {
        return $this->hasMany(InventoryRequisition::class, 'destination_inventory_location_id');
    }

    public function destinationRequisitions(): HasMany
    {
        return $this->requestingRequisitions();
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'inventory_location_items')
            ->withPivot([
                'id',
                'branch_id',
                'minimum_stock_level',
                'reorder_level',
                'default_selling_price',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }
}
