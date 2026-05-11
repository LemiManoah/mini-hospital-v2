<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Enums\InsurancePolicyType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InsurancePolicy extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'insurance_package_id' => 'string',
        'policy_type' => InsurancePolicyType::class,
        'effective_from' => 'date',
        'effective_to' => 'date',
        'status' => GeneralStatus::class,
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /**
     * @return BelongsTo<InsurancePackage, $this>
     */
    public function insurancePackage(): BelongsTo
    {
        return $this->belongsTo(InsurancePackage::class);
    }

    /**
     * @return HasMany<InsurancePolicyItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InsurancePolicyItem::class);
    }
}
