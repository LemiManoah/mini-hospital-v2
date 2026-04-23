<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabResultEntry extends Model
{
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'lab_request_item_id' => 'string',
        'entered_by' => 'string',
        'entered_at' => 'datetime',
        'reviewed_by' => 'string',
        'reviewed_at' => 'datetime',
        'approved_by' => 'string',
        'approved_at' => 'datetime',
        'released_by' => 'string',
        'released_at' => 'datetime',
        'corrected_by' => 'string',
        'corrected_at' => 'datetime',
    ];

    /** @return BelongsTo<LabRequestItem, $this> */
    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(LabRequestItem::class, 'lab_request_item_id');
    }

    /** @return HasMany<LabResultValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(LabResultValue::class, 'lab_result_entry_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /** @return BelongsTo<Staff, $this> */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'entered_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'released_by');
    }

    /** @return BelongsTo<Staff, $this> */
    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'corrected_by');
    }
}
