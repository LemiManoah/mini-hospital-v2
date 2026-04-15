<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DispensingRecordItemAllocation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'dispensing_record_item_id' => 'string',
        'inventory_batch_id' => 'string',
        'quantity' => 'decimal:3',
        'unit_cost_snapshot' => 'decimal:2',
        'expiry_date_snapshot' => 'date',
    ];

    public function dispensingRecordItem(): BelongsTo
    {
        return $this->belongsTo(DispensingRecordItem::class);
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
