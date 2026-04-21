<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryRequisitionStatus;
use App\Enums\Priority;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InventoryRequisition extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<self>> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'source_inventory_location_id' => 'string',
        'destination_inventory_location_id' => 'string',
        'status' => InventoryRequisitionStatus::class,
        'priority' => Priority::class,
        'requisition_date' => 'date',
        'created_by' => 'string',
        'updated_by' => 'string',
        'submitted_by' => 'string',
        'submitted_at' => 'datetime',
        'approved_by' => 'string',
        'approved_at' => 'datetime',
        'rejected_by' => 'string',
        'rejected_at' => 'datetime',
        'cancelled_by' => 'string',
        'cancelled_at' => 'datetime',
        'issued_by' => 'string',
        'issued_at' => 'datetime',
    ];

    /** @return BelongsTo<InventoryLocation, $this> */
    public function fulfillingLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'source_inventory_location_id');
    }

    /** @return BelongsTo<InventoryLocation, $this> */
    public function sourceLocation(): BelongsTo
    {
        return $this->fulfillingLocation();
    }

    /** @return BelongsTo<InventoryLocation, $this> */
    public function requestingLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'destination_inventory_location_id');
    }

    /** @return BelongsTo<InventoryLocation, $this> */
    public function destinationLocation(): BelongsTo
    {
        return $this->requestingLocation();
    }

    /** @return HasMany<InventoryRequisitionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryRequisitionItem::class);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === InventoryRequisitionStatus::Draft;
    }

    public function canBeApproved(): bool
    {
        return $this->status === InventoryRequisitionStatus::Submitted;
    }

    public function canBeRejected(): bool
    {
        return $this->status === InventoryRequisitionStatus::Submitted;
    }

    public function canBeIssued(): bool
    {
        return in_array($this->status, [
            InventoryRequisitionStatus::Approved,
            InventoryRequisitionStatus::PartiallyIssued,
        ], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            InventoryRequisitionStatus::Draft,
            InventoryRequisitionStatus::Submitted,
        ], true);
    }
}
