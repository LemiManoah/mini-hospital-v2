<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $visit_billing_id
 * @property-read string|null $patient_visit_id
 * @property-read string|null $receipt_number
 * @property-read string|null $payment_method
 * @property-read string|null $reference_number
 * @property-read string|null $notes
 * @property-read string $amount
 * @property-read bool $is_refund
 * @property-read Carbon|null $payment_date
 * @property-read Carbon|null $deleted_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read VisitBilling|null $billing
 * @property-read PatientVisit|null $visit
 * @property-read FacilityBranch|null $branch
 */
final class Payment extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<Factory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_billing_id' => 'string',
        'patient_visit_id' => 'string',
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'is_refund' => 'boolean',
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
}
