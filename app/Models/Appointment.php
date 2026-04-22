<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Appointment extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'patient_id' => 'string',
        'doctor_id' => 'string',
        'clinic_id' => 'string',
        'appointment_category_id' => 'string',
        'appointment_mode_id' => 'string',
        'status' => AppointmentStatus::class,
        'appointment_date' => 'date',
        'is_walk_in' => 'boolean',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_by' => 'string',
        'rescheduled_from_appointment_id' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return BelongsTo<AppointmentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AppointmentCategory::class, 'appointment_category_id');
    }

    /**
     * @return BelongsTo<AppointmentMode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(AppointmentMode::class, 'appointment_mode_id');
    }

    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @return HasOne<PatientVisit, $this>
     */
    public function visit(): HasOne
    {
        return $this->hasOne(PatientVisit::class, 'appointment_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_appointment_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isFinalized();
    }
}
