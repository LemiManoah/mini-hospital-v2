<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ScheduleExceptionType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class DoctorScheduleException extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\DoctorScheduleExceptionFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'facility_branch_id',
        'doctor_id',
        'clinic_id',
        'exception_date',
        'start_time',
        'end_time',
        'type',
        'reason',
        'is_all_day',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'doctor_id' => 'string',
        'clinic_id' => 'string',
        'exception_date' => 'date',
        'type' => ScheduleExceptionType::class,
        'is_all_day' => 'boolean',
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
