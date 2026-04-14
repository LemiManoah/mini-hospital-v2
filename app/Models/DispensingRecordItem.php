<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrescriptionItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DispensingRecordItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'dispensing_record_id' => 'string',
        'prescription_item_id' => 'string',
        'inventory_item_id' => 'string',
        'substitution_inventory_item_id' => 'string',
        'prescribed_quantity' => 'decimal:3',
        'dispensed_quantity' => 'decimal:3',
        'balance_quantity' => 'decimal:3',
        'dispense_status' => PrescriptionItemStatus::class,
        'external_pharmacy' => 'boolean',
    ];

    public function dispensingRecord(): BelongsTo
    {
        return $this->belongsTo(DispensingRecord::class);
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function substitutionInventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'substitution_inventory_item_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DispensingRecordItemAllocation::class);
    }
}
