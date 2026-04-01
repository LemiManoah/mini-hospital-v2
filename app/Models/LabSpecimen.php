<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabSpecimenStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LabSpecimen extends Model
{
    /** @use HasFactory<\Database\Factories\LabSpecimenFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'lab_request_item_id' => 'string',
        'specimen_type_id' => 'string',
        'status' => LabSpecimenStatus::class,
        'collected_by' => 'string',
        'collected_at' => 'datetime',
        'outside_sample' => 'boolean',
    ];

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(LabRequestItem::class, 'lab_request_item_id');
    }

    public function specimenType(): BelongsTo
    {
        return $this->belongsTo(SpecimenType::class, 'specimen_type_id');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'collected_by');
    }
}
