<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Consultation extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\ConsultationFactory> */
    use HasFactory;

    use HasUuids;

    public const array OUTCOMES = [
        'discharged',
        'admitted',
        'referred',
        'follow_up_required',
        'transferred',
        'deceased',
        'left_against_advice',
    ];
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_id' => 'string',
        'doctor_id' => 'string',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'secondary_diagnoses' => 'array',
        'follow_up_days' => 'integer',
        'is_referred' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class, 'consultation_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'consultation_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'consultation_id');
    }

    public function facilityServiceOrders(): HasMany
    {
        return $this->hasMany(FacilityServiceOrder::class, 'consultation_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
