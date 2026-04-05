<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockAdjustmentStatus;
use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StockAdjustment extends Model
{
    use BelongsToBranch;
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\StockAdjustmentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'branch_id' => 'string',
        'inventory_location_id' => 'string',
        'status' => StockAdjustmentStatus::class,
        'adjustment_date' => 'date',
        'created_by' => 'string',
        'updated_by' => 'string',
        'submitted_by' => 'string',
        'submitted_at' => 'datetime',
        'reviewed_by' => 'string',
        'reviewed_at' => 'datetime',
        'approved_by' => 'string',
        'approved_at' => 'datetime',
        'rejected_by' => 'string',
        'rejected_at' => 'datetime',
        'posted_by' => 'string',
        'posted_at' => 'datetime',
    ];

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function workflowStatus(): string
    {
        if ($this->status === StockAdjustmentStatus::Posted) {
            return 'posted';
        }

        if ($this->rejected_at !== null) {
            return 'rejected';
        }

        if ($this->approved_at !== null) {
            return 'approved';
        }

        if ($this->reviewed_at !== null) {
            return 'reviewed';
        }

        if ($this->submitted_at !== null) {
            return 'submitted';
        }

        return 'draft';
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === StockAdjustmentStatus::Draft
            && $this->submitted_at === null
            && $this->rejected_at === null
            && $this->posted_at === null;
    }

    public function canBeReviewed(): bool
    {
        return $this->status === StockAdjustmentStatus::Draft
            && $this->submitted_at !== null
            && $this->reviewed_at === null
            && $this->approved_at === null
            && $this->rejected_at === null
            && $this->posted_at === null;
    }

    public function canBeApproved(): bool
    {
        return $this->status === StockAdjustmentStatus::Draft
            && $this->reviewed_at !== null
            && $this->approved_at === null
            && $this->rejected_at === null
            && $this->posted_at === null;
    }

    public function canBeRejected(): bool
    {
        return $this->status === StockAdjustmentStatus::Draft
            && $this->submitted_at !== null
            && $this->approved_at === null
            && $this->rejected_at === null
            && $this->posted_at === null;
    }

    public function canBePosted(): bool
    {
        return $this->status === StockAdjustmentStatus::Draft
            && $this->approved_at !== null
            && $this->rejected_at === null
            && $this->posted_at === null;
    }
}
