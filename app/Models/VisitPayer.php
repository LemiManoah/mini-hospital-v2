<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayerType;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string $id
 * @property-read string|null $tenant_id
 * @property-read string|null $patient_visit_id
 * @property-read string|null $insurance_company_id
 * @property-read string|null $insurance_package_id
 * @property-read PayerType|null $billing_type
 * @property-read CarbonInterface|null $deleted_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read PatientVisit|null $visit
 * @property-read InsuranceCompany|null $insuranceCompany
 * @property-read InsurancePackage|null $insurancePackage
 * @property-read VisitBilling|null $billing
 */
final class VisitPayer extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'patient_visit_id' => 'string',
        'insurance_company_id' => 'string',
        'insurance_package_id' => 'string',
        'billing_type' => PayerType::class,
    ];

    /**
     * @return BelongsTo<PatientVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
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
     * @return HasOne<VisitBilling, $this>
     */
    public function billing(): HasOne
    {
        return $this->hasOne(VisitBilling::class);
    }
}
