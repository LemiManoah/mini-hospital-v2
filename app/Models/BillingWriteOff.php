<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingWriteOffStatus;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $visit_billing_id
 * @property-read string|null $patient_visit_id
 * @property-read numeric-string|null $amount
 * @property-read string|null $reason
 * @property-read BillingWriteOffStatus|null $status
 * @property-read string|null $notes
 * @property-read string|null $requested_by
 * @property-read CarbonInterface|null $requested_at
 * @property-read string|null $approved_by
 * @property-read CarbonInterface|null $approved_at
 * @property-read string|null $reversed_by
 * @property-read CarbonInterface|null $reversed_at
 * @property-read string|null $reversal_reason
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read VisitBilling|null $billing
 * @property-read PatientVisit|null $visit
 * @property-read FacilityBranch|null $branch
 */
final class BillingWriteOff extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_billing_id' => 'string',
        'patient_visit_id' => 'string',
        'amount' => 'decimal:2',
        'status' => BillingWriteOffStatus::class,
        'requested_by' => 'string',
        'requested_at' => 'datetime',
        'approved_by' => 'string',
        'approved_at' => 'datetime',
        'reversed_by' => 'string',
        'reversed_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /** @return BelongsTo<VisitBilling, $this> */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(VisitBilling::class, 'visit_billing_id');
    }

    /** @return BelongsTo<PatientVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /** @return BelongsTo<FacilityBranch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', BillingWriteOffStatus::APPROVED->value);
    }
}
