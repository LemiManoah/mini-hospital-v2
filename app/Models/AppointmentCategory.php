<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AppointmentCategory extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\AppointmentCategoryFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'clinic_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }
}
