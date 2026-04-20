<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacyPosSaleItemAllocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PharmacyPosSaleItemAllocation extends Model
{
    /** @use HasFactory<PharmacyPosSaleItemAllocationFactory> */
    use HasFactory;

    use HasUuids;

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(PharmacyPosSaleItem::class, 'pharmacy_pos_sale_item_id');
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    protected function casts(): array
    {
        return [
            'pharmacy_pos_sale_item_id' => 'string',
            'inventory_batch_id' => 'string',
            'quantity' => 'decimal:3',
            'unit_cost_snapshot' => 'decimal:2',
            'expiry_date_snapshot' => 'date',
        ];
    }
}
