<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property-read string|null $insured_visit_claim_id
 * @property-read string|null $insurance_company_invoice_id
 * @property-read string|null $insurance_company_invoice_payment_id
 * @property-read CarbonInterface|null $allocation_date
 * @property-read numeric-string|null $allocated_amount
 * @property-read string|null $notes
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read InsuredVisitClaim|null $claim
 * @property-read InsuranceCompanyInvoice|null $insuranceCompanyInvoice
 * @property-read InsuranceCompanyInvoicePayment|null $insuranceCompanyInvoicePayment
 * @property-read FacilityBranch|null $branch
 */
final class InsuranceClaimAllocation extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'insured_visit_claim_id' => 'string',
        'insurance_company_invoice_id' => 'string',
        'insurance_company_invoice_payment_id' => 'string',
        'allocation_date' => 'date',
        'allocated_amount' => 'decimal:2',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @return BelongsTo<InsuredVisitClaim, $this>
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(InsuredVisitClaim::class, 'insured_visit_claim_id');
    }

    /**
     * @return BelongsTo<InsuranceCompanyInvoice, $this>
     */
    public function insuranceCompanyInvoice(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyInvoice::class);
    }

    /**
     * @return BelongsTo<InsuranceCompanyInvoicePayment, $this>
     */
    public function insuranceCompanyInvoicePayment(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyInvoicePayment::class);
    }
}
