<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $prescription_id
 * @property-read string $inventory_item_id
 * @property-read string $dosage
 * @property-read string $frequency
 * @property-read string $route
 * @property-read int $duration_days
 * @property-read int $quantity
 * @property-read string|null $instructions
 * @property-read bool $is_prn
 * @property-read string|null $prn_reason
 * @property-read bool $is_external_pharmacy
 * @property-read PrescriptionItemStatus|null $status
 * @property-read Carbon|null $dispensed_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Prescription|null $prescription
 * @property-read InventoryItem|null $inventoryItem
 */
final class PrescriptionItem extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'prescription_id' => 'string',
        'inventory_item_id' => 'string',
        'duration_days' => 'integer',
        'quantity' => 'integer',
        'is_prn' => 'boolean',
        'is_external_pharmacy' => 'boolean',
        'status' => PrescriptionItemStatus::class,
        'dispensed_at' => 'datetime',
    ];

    /** @return BelongsTo<Prescription, $this> */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /** @return HasMany<DispensingRecordItem, $this> */
    public function dispensingRecordItems(): HasMany
    {
        return $this->hasMany(DispensingRecordItem::class);
    }
}
