<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Enums\InsuranceCopayType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InsurancePolicyItem extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'insurance_policy_id' => 'string',
        'charge_master_id' => 'string',
        'price' => 'decimal:2',
        'copay_type' => InsuranceCopayType::class,
        'copay_value' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'status' => GeneralStatus::class,
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /**
     * @return BelongsTo<InsurancePolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    /**
     * @return BelongsTo<ChargeMaster, $this>
     */
    public function chargeMaster(): BelongsTo
    {
        return $this->belongsTo(ChargeMaster::class);
    }
}
