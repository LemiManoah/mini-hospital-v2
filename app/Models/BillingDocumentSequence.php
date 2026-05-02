<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingDocumentType;
use App\Enums\BillingSequenceResetPeriod;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BillingDocumentSequence extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'document_type' => BillingDocumentType::class,
        'next_number' => 'integer',
        'padding' => 'integer',
        'reset_period' => BillingSequenceResetPeriod::class,
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    /** @return BelongsTo<FacilityBranch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }
}
