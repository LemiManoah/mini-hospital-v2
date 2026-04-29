<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * @property-read int $id
 * @property-read string|null $tenant_id
 * @property-read string|null $branch_id
 * @property-read string|null $staff_id
 * @property-read string|null $ip_address
 * @property-read string|null $user_agent
 * @property-read Tenant|null $tenant
 * @property-read FacilityBranch|null $branch
 * @property-read Staff|null $staff
 */
final class Activity extends SpatieActivity
{
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
        return $this->belongsTo(FacilityBranch::class, 'branch_id');
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'tenant_id' => 'string',
            'branch_id' => 'string',
            'staff_id' => 'string',
            'ip_address' => 'string',
            'user_agent' => 'string',
        ];
    }
}
