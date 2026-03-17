<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InsuranceCompanyInvoice extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\InsuranceCompanyInvoiceFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'insurance_company_id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'due_date' => 'date',
        'bill_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'status' => BillingStatus::class,
        'is_printed' => 'boolean',
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
     * @return BelongsTo<InsuranceCompany, $this>
     */
    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * @return HasMany<InsuranceCompanyInvoicePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InsuranceCompanyInvoicePayment::class);
    }
}
