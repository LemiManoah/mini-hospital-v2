<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabSpecimenStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LabSpecimen extends Model
{
    use HasUuids;

    protected $casts = [
        'lab_order_item_id' => 'string',
        'specimen_type_id' => 'string',
        'status' => LabSpecimenStatus::class,
        'collected_by' => 'string',
        'collected_at' => 'datetime',
        'rejected_by' => 'string',
        'rejected_at' => 'datetime',
        'outside_sample' => 'boolean',
    ];

    /**
     * @return BelongsTo<LabOrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(LabOrderItem::class, 'lab_order_item_id');
    }

    /**
     * @return BelongsTo<SpecimenType, $this>
     */
    public function specimenType(): BelongsTo
    {
        return $this->belongsTo(SpecimenType::class, 'specimen_type_id');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'collected_by');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'rejected_by');
    }
}
