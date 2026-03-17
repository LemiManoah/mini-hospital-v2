<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScheduleDay;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DoctorSchedule extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\DoctorScheduleFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'doctor_id' => 'string',
        'clinic_id' => 'string',
        'day_of_week' => ScheduleDay::class,
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }
}
