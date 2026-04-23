<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string|null $stock_reconciliation_id
 * @property-read string|null $inventory_item_id
 * @property-read string|null $inventory_batch_id
 * @property-read string|null $notes
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read numeric-string|null $expected_quantity
 * @property-read numeric-string|null $actual_quantity
 * @property-read numeric-string|null $variance_quantity
 * @property-read numeric-string|null $quantity_delta
 * @property-read numeric-string|null $unit_cost
 * @property-read CarbonInterface|null $expiry_date
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Reconciliation|null $reconciliation
 * @property-read InventoryItem|null $inventoryItem
 * @property-read InventoryBatch|null $inventoryBatch
 */
final class ReconciliationItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'stock_reconciliation_items';

    protected $casts = [
        'stock_reconciliation_id' => 'string',
        'inventory_item_id' => 'string',
        'inventory_batch_id' => 'string',
        'expected_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'quantity_delta' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * @return BelongsTo<Reconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class, 'stock_reconciliation_id');
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * @return BelongsTo<InventoryBatch, $this>
     */
    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
