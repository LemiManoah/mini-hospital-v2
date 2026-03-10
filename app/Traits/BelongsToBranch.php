<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\FacilityBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    /**
     * @return BelongsTo<FacilityBranch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(FacilityBranch::class, 'branch_id');
    }

    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope());

        static::creating(function ($model): void {
            if (empty($model->branch_id)) {
                $branchId = BranchContext::getActiveBranchId();

                if ($branchId !== null) {
                    $model->branch_id = $branchId;
                }
            }
        });
    }
}
