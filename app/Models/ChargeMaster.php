<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillableItemType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ChargeMaster extends Model
{
    use BelongsToTenant;
    use HasUuids;
    use SoftDeletes;

    protected $casts = [
        'tenant_id' => 'string',
        'facility_branch_id' => 'string',
        'billable_type' => BillableItemType::class,
        'billable_id' => 'string',
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
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
     * @return HasMany<VisitCharge, $this>
     */
    public function visitCharges(): HasMany
    {
        return $this->hasMany(VisitCharge::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function effectiveOn(Builder $query, string $date): void
    {
        $query
            ->where('is_active', true)
            ->where(function (Builder $rangeQuery) use ($date): void {
                $rangeQuery->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date);
            })
            ->where(function (Builder $rangeQuery) use ($date): void {
                $rangeQuery->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }
}
