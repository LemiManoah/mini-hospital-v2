<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PatientVisit extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\PatientVisitFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'patient_id' => 'string',
        'facility_branch_id' => 'string',
        'clinic_id' => 'string',
        'doctor_id' => 'string',
        'appointment_id' => 'string',
        'is_emergency' => 'boolean',
        'visit_type' => VisitType::class,
        'status' => VisitStatus::class,
        'registered_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<FacilityBranch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /** @return BelongsTo<Clinic, $this> */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** @return BelongsTo<Staff, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /** @return HasOne<VisitPayer, $this> */
    public function payer(): HasOne
    {
        return $this->hasOne(VisitPayer::class, 'patient_visit_id');
    }

    /** @return HasOne<TriageRecord, $this> */
    public function triage(): HasOne
    {
        return $this->hasOne(TriageRecord::class, 'visit_id');
    }

    /** @return HasOne<Consultation, $this> */
    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class, 'visit_id');
    }

    /** @return HasMany<LabRequest, $this> */
    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class, 'visit_id');
    }

    /** @return HasMany<ImagingRequest, $this> */
    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'visit_id');
    }

    /** @return HasMany<Prescription, $this> */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'visit_id');
    }

    /** @return HasMany<FacilityServiceOrder, $this> */
    public function facilityServiceOrders(): HasMany
    {
        return $this->hasMany(FacilityServiceOrder::class, 'visit_id');
    }

    /** @return HasOne<VisitBilling, $this> */
    public function billing(): HasOne
    {
        return $this->hasOne(VisitBilling::class, 'patient_visit_id');
    }

    /** @return HasMany<VisitCharge, $this> */
    public function charges(): HasMany
    {
        return $this->hasMany(VisitCharge::class, 'patient_visit_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'patient_visit_id');
    }
}
