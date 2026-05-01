<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InsuredVisitClaimStatus;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $visit_billing_id
 * @property-read string|null $patient_visit_id
 * @property-read string|null $insurance_company_id
 * @property-read string|null $insurance_package_id
 * @property-read string|null $insurance_company_invoice_id
 * @property-read string|null $claim_reference
 * @property-read numeric-string|null $claimed_amount
 * @property-read numeric-string|null $approved_amount
 * @property-read numeric-string|null $rejected_amount
 * @property-read numeric-string|null $copay_amount
 * @property-read numeric-string|null $paid_amount
 * @property-read InsuredVisitClaimStatus|null $status
 * @property-read CarbonInterface|null $invoiced_at
 * @property-read CarbonInterface|null $submitted_at
 * @property-read CarbonInterface|null $paid_at
 * @property-read CarbonInterface|null $rejected_at
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read VisitBilling|null $billing
 * @property-read PatientVisit|null $visit
 * @property-read FacilityBranch|null $branch
 * @property-read InsuranceCompany|null $insuranceCompany
 * @property-read InsurancePackage|null $insurancePackage
 * @property-read InsuranceCompanyInvoice|null $insuranceCompanyInvoice
 * @property-read Collection<int, InsuranceClaimAllocation> $allocations
 */
final class InsuredVisitClaim extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'visit_billing_id' => 'string',
        'patient_visit_id' => 'string',
        'insurance_company_id' => 'string',
        'insurance_package_id' => 'string',
        'insurance_company_invoice_id' => 'string',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'copay_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'status' => InsuredVisitClaimStatus::class,
        'invoiced_at' => 'datetime',
        'submitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<VisitBilling, $this>
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(VisitBilling::class, 'visit_billing_id');
    }

    /**
     * @return BelongsTo<PatientVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @return BelongsTo<InsuranceCompany, $this>
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * @return BelongsTo<InsurancePackage, $this>
     */
    public function insurancePackage(): BelongsTo
    {
        return $this->belongsTo(InsurancePackage::class);
    }

    /**
     * @return BelongsTo<InsuranceCompanyInvoice, $this>
     */
    public function insuranceCompanyInvoice(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyInvoice::class);
    }

    /**
     * @return HasMany<InsuranceClaimAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(InsuranceClaimAllocation::class, 'insured_visit_claim_id');
    }
}
