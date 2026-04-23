<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\MobilityStatus;
use App\Enums\TriageGrade;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $visit_id
 * @property-read string|null $nurse_id
 * @property-read string|null $assigned_clinic_id
 * @property-read CarbonInterface|null $triage_datetime
 * @property-read TriageGrade|null $triage_grade
 * @property-read AttendanceType|null $attendance_type
 * @property-read ConsciousLevel|null $conscious_level
 * @property-read MobilityStatus|null $mobility_status
 * @property-read bool $requires_priority
 * @property-read bool $is_pediatric
 * @property-read bool $poisoning_case
 * @property-read bool $snake_bite_case
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read PatientVisit|null $visit
 * @property-read Staff|null $nurse
 * @property-read Clinic|null $assignedClinic
 * @property-read Collection<int, VitalSign> $vitalSigns
 */
final class TriageRecord extends Model
{
    use BelongsToTenant;
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

    /**
     * @return BelongsTo<PatientVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'nurse_id');
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function assignedClinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'assigned_clinic_id');
    }

    /**
     * @return HasMany<VitalSign, $this>
     */
    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class, 'triage_id')->latest('recorded_at');
    }
}
