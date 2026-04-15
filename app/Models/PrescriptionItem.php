<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PrescriptionItem extends Model
{
    /** @use HasFactory<\Database\Factories\PrescriptionItemFactory> */
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

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function dispensingRecordItems(): HasMany
    {
        return $this->hasMany(DispensingRecordItem::class);
    }
}
