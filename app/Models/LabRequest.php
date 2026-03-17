<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabBillingStatus;
use App\Enums\LabRequestStatus;
use App\Enums\Priority;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LabRequest extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\LabRequestFactory> */
    use HasFactory;

    use HasUuids;
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_id' => 'string',
        'consultation_id' => 'string',
        'requested_by' => 'string',
        'request_date' => 'datetime',
        'priority' => Priority::class,
        'status' => LabRequestStatus::class,
        'is_stat' => 'boolean',
        'billing_status' => LabBillingStatus::class,
        'completed_at' => 'datetime',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class, 'request_id');
    }
}
