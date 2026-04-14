<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DispensingRecordStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DispensingRecord extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    use HasFactory;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'visit_id' => 'string',
        'prescription_id' => 'string',
        'inventory_location_id' => 'string',
        'dispensed_by' => 'string',
        'dispensed_at' => 'datetime',
        'status' => DispensingRecordStatus::class,
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispensingRecordItem::class);
    }
}
