<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FacilityServiceOrderStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class FacilityServiceOrder extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\FacilityServiceOrderFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'facility_branch_id',
        'visit_id',
        'consultation_id',
        'facility_service_id',
        'ordered_by',
        'status',
        'clinical_notes',
        'service_instructions',
        'ordered_at',
        'performed_by',
        'completed_at',
        'cancellation_reason',
    ];

    #[Override]
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(FacilityService::class, 'facility_service_id');
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'ordered_by');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by');
    }
}
