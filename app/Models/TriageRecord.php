<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\MobilityStatus;
use App\Enums\TriageGrade;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TriageRecord extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\TriageRecordFactory> */
    use HasFactory;

    use HasUuids;
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_id' => 'string',
        'nurse_id' => 'string',
        'assigned_clinic_id' => 'string',
        'triage_datetime' => 'datetime',
        'triage_grade' => TriageGrade::class,
        'attendance_type' => AttendanceType::class,
        'conscious_level' => ConsciousLevel::class,
        'mobility_status' => MobilityStatus::class,
        'requires_priority' => 'boolean',
        'is_pediatric' => 'boolean',
        'poisoning_case' => 'boolean',
        'snake_bite_case' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'nurse_id');
    }

    public function assignedClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'assigned_clinic_id');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class, 'triage_id')->latest('recorded_at');
    }
}
