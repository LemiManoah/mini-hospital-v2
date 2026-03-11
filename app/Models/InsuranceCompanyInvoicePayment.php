<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class InsuranceCompanyInvoicePayment extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\InsuranceCompanyInvoicePaymentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'tenant_id',
        'facility_branch_id',
        'insurance_company_invoice_id',
        'payment_date',
        'receipt',
        'paid_amount',
        'created_by',
        'updated_by',
    ];

    #[Override]
    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'insurance_company_invoice_id' => 'string',
        'payment_date' => 'date',
        'paid_amount' => 'decimal:2',
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
     * @return BelongsTo<InsuranceCompanyInvoice, $this>
     */
    public function insuranceCompanyInvoice(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompanyInvoice::class);
    }
}
