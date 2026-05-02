<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingDepositStatus;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $patient_id
 * @property-read string|null $patient_visit_id
 * @property-read string|null $visit_billing_id
 * @property-read string|null $deposit_number
 * @property-read string|null $payment_method_id
 * @property-read string|null $payment_method
 * @property-read string|null $reference_number
 * @property-read numeric-string|null $amount
 * @property-read numeric-string|null $applied_amount
 * @property-read numeric-string|null $refunded_amount
 * @property-read BillingDepositStatus|null $status
 * @property-read CarbonInterface|null $received_at
 * @property-read CarbonInterface|null $applied_at
 * @property-read CarbonInterface|null $refunded_at
 * @property-read string|null $notes
 * @property-read Patient|null $patient
 * @property-read PatientVisit|null $visit
 * @property-read VisitBilling|null $billing
 */
final class BillingDeposit extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'patient_id' => 'string',
        'patient_visit_id' => 'string',
        'visit_billing_id' => 'string',
        'payment_method_id' => 'string',
        'amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'status' => BillingDepositStatus::class,
        'received_at' => 'datetime',
        'applied_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<PatientVisit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /** @return BelongsTo<VisitBilling, $this> */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(VisitBilling::class, 'visit_billing_id');
    }
}
