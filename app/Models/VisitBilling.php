<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\BillingStatus;
use App\Enums\PayerType;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $facility_branch_id
 * @property-read string|null $patient_visit_id
 * @property-read string|null $visit_payer_id
 * @property-read string|null $insurance_company_id
 * @property-read string|null $insurance_package_id
 * @property-read PayerType|null $payer_type
 * @property-read numeric-string|null $gross_amount
 * @property-read numeric-string|null $discount_amount
 * @property-read numeric-string|null $paid_amount
 * @property-read numeric-string|null $balance_amount
 * @property-read BillingStatus|null $status
 * @property-read CarbonInterface|null $billed_at
 * @property-read CarbonInterface|null $settled_at
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read PatientVisit|null $visit
 * @property-read VisitPayer|null $visitPayer
 * @property-read FacilityBranch|null $branch
 * @property-read InsuranceCompany|null $insuranceCompany
 * @property-read InsurancePackage|null $insurancePackage
 * @property-read Collection<int, VisitCharge> $charges
 * @property-read Collection<int, Payment> $payments
 */
final class VisitBilling extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'patient_visit_id' => 'string',
        'visit_payer_id' => 'string',
        'insurance_company_id' => 'string',
        'insurance_package_id' => 'string',
        'payer_type' => PayerType::class,
        'gross_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'status' => BillingStatus::class,
        'billed_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PatientVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    /**
     * @return BelongsTo<VisitPayer, $this>
     */
    public function visitPayer(): BelongsTo
    {
        return $this->belongsTo(VisitPayer::class, 'visit_payer_id');
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
     * @return HasMany<VisitCharge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(VisitCharge::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
