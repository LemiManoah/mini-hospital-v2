<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryLocationItem extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'inventory_location_id' => 'string',
        'inventory_item_id' => 'string',
        'minimum_stock_level' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'default_selling_price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<InventoryLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }
}
