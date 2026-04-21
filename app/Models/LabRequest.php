<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LabBillingStatus;
use App\Enums\LabRequestStatus;
use App\Enums\Priority;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string $facility_branch_id
 * @property-read string $visit_id
 * @property-read string|null $consultation_id
 * @property-read string $requested_by
 * @property-read Carbon|null $request_date
 * @property-read Priority|null $priority
 * @property-read LabRequestStatus $status
 * @property-read bool $is_stat
 * @property-read LabBillingStatus|null $billing_status
 * @property-read Carbon|null $completed_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Consultation|null $consultation
 * @property-read PatientVisit|null $visit
 * @property-read Staff|null $requestedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LabRequestItem> $items
 */
final class LabRequest extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<Factory> */
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

    /** @return BelongsTo<Consultation, $this> */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consultation_id');
    }

    /** @return BelongsTo<PatientVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'visit_id');
    }

    /** @return BelongsTo<Staff, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    /** @return HasMany<LabRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class, 'request_id');
    }
}
