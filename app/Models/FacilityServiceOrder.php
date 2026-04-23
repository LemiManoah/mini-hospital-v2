<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FacilityServiceOrderStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FacilityServiceOrder extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_id' => 'string',
        'consultation_id' => 'string',
        'facility_service_id' => 'string',
        'ordered_by' => 'string',
        'status' => FacilityServiceOrderStatus::class,
        'ordered_at' => 'datetime',
        'performed_by' => 'string',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<FacilityService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(FacilityService::class, 'facility_service_id');
    }

    /**
     * @return BelongsTo<Consultation, $this>
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

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
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'ordered_by');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by');
    }
}
