<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsultationType;
use App\Enums\VisitType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConsultationTariff extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_type' => VisitType::class,
        'consultation_type' => ConsultationType::class,
        'facility_service_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @return BelongsTo<FacilityService, $this>
     */
    public function facilityService(): BelongsTo
    {
        return $this->belongsTo(FacilityService::class, 'facility_service_id');
    }
}
