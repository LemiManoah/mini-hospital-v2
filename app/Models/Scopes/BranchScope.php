<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (! Auth::hasUser() && $model instanceof Authenticatable) {
            return;
        }

        if (! Auth::check()) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->is_support && $user->tenant_id === null) {
            return;
        }

        $branchId = BranchContext::getActiveBranchId();
        $tableName = $model->getTable();

        if ($branchId !== null) {
            $builder->where($tableName.'.branch_id', $branchId);

            return;
        }

        if ($user->tenant_id !== null) {
            $builder->whereRaw('1 = 0');
        }
    }
}
