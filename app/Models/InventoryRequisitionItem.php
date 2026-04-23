<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryRequisitionItem extends Model
{
    use HasUuids;

    protected $casts = [
        'inventory_requisition_id' => 'string',
        'inventory_item_id' => 'string',
        'requested_quantity' => 'decimal:3',
        'approved_quantity' => 'decimal:3',
        'issued_quantity' => 'decimal:3',
    ];

    /** @return BelongsTo<InventoryRequisition, $this> */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(InventoryRequisition::class, 'inventory_requisition_id');
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function remainingApprovedQuantity(): float
    {
        return max(0.0, (float) $this->approved_quantity - (float) $this->issued_quantity);
    }
}
