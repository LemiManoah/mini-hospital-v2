<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImagingLaterality;
use App\Enums\ImagingModality;
use App\Enums\ImagingPriority;
use App\Enums\ImagingRequestStatus;
use App\Enums\PregnancyStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImagingRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ImagingRequestFactory> */
    use HasFactory;

    use HasUuids;

    protected $casts = [
        'visit_id' => 'string',
        'consultation_id' => 'string',
        'requested_by' => 'string',
        'modality' => ImagingModality::class,
        'laterality' => ImagingLaterality::class,
        'priority' => ImagingPriority::class,
        'status' => ImagingRequestStatus::class,
        'scheduled_date' => 'datetime',
        'scheduled_by' => 'string',
        'requires_contrast' => 'boolean',
        'pregnancy_status' => PregnancyStatus::class,
        'radiation_dose_msv' => 'float',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'scheduled_by');
    }
}
