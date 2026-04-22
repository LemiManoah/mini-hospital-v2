<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $dispensing_record_item_id
 * @property-read string $inventory_batch_id
 * @property-read string $quantity
 * @property-read string|null $unit_cost_snapshot
 * @property-read string|null $batch_number_snapshot
 * @property-read Carbon|null $expiry_date_snapshot
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read DispensingRecordItem|null $dispensingRecordItem
 * @property-read InventoryBatch|null $inventoryBatch
 */
final class DispensingRecordItemAllocation extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'dispensing_record_item_id' => 'string',
        'inventory_batch_id' => 'string',
        'quantity' => 'decimal:3',
        'unit_cost_snapshot' => 'decimal:2',
        'expiry_date_snapshot' => 'date',
    ];

    /** @return BelongsTo<DispensingRecordItem, $this> */
    public function dispensingRecordItem(): BelongsTo
    {
        return $this->belongsTo(DispensingRecordItem::class);
    }

    /** @return BelongsTo<InventoryBatch, $this> */
    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
