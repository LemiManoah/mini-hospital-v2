<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property-read string $tenant_id
 * @property-read string $facility_branch_id
 * @property-read string $currency_id
 * @property-read bool $is_default
 * @property-read string|null $created_by
 * @property-read string|null $updated_by
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read FacilityBranch $branch
 * @property-read Currency $currency
 */
final class FacilityBranchCurrency extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'facility_branch_id',
        'currency_id',
        'is_default',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'facility_branch_id' => 'string',
            'currency_id' => 'string',
            'is_default' => 'boolean',
            'created_by' => 'string',
            'updated_by' => 'string',
        ];
    }

    /** @return BelongsTo<FacilityBranch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'facility_branch_id');
    }

    /** @return BelongsTo<Currency, $this> */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
