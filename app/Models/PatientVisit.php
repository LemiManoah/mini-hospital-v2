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
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class PatientVisit extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\PatientVisitFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'patient_id',
        'facility_branch_id',
        'visit_number',
        'visit_type',
        'status',
        'clinic_id',
        'doctor_id',
        'is_emergency',
        'complaint',
        'notes',
        'registered_at',
        'registered_by',
        'started_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'patient_id' => 'string',
        'facility_branch_id' => 'string',
        'clinic_id' => 'string',
        'doctor_id' => 'string',
        'is_emergency' => 'boolean',
        'visit_type' => VisitType::class,
        'status' => VisitStatus::class,
        'registered_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function payer(): HasOne
    {
        return $this->hasOne(VisitPayer::class, 'patient_visit_id');
    }
}
