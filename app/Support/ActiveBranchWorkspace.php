<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ActiveBranchWorkspace
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, string $column = 'facility_branch_id'): Builder
    {
        $branchId = BranchContext::getActiveBranchId();

        if (! is_string($branchId) || $branchId === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, $branchId);
    }

    public function authorizeModel(
        Model $model,
        string $column = 'facility_branch_id',
        string $message = 'You do not have access to this record in the active branch.',
    ): void {
        $branchId = BranchContext::getActiveBranchId();
        $recordBranchId = $model->getAttribute($column);

        abort_unless(
            is_string($branchId)
                && $branchId !== ''
                && is_string($recordBranchId)
                && $recordBranchId === $branchId,
            403,
            $message,
        );
    }
}
