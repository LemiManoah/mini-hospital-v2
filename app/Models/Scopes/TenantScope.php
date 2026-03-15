<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip if running in console (migrations, etc.)
        if (app()->runningInConsole()) {
            return;
        }

        // Avoid infinite recursion if we are currently resolving the authenticated user.
        // Auth::check() triggers user resolution which leads back here if applied to the User model.
        if (! Auth::hasUser() && $model instanceof Authenticatable) {
            return;
        }

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            // Support users can access all tenants, so don't filter by tenant_id
            // UNLESS they have a tenant_id set (meaning they've switched to a specific tenant)
            if ($user->is_support && $user->tenant_id === null) {
                return;
            }

            if ($user->tenant_id !== null) {
                /** @var Model $modelInstance */
                $modelInstance = $builder->getModel();
                $tableName = $modelInstance->getTable();
                $builder->where(function (Builder $query) use ($tableName, $user): void {
                    $query->where($tableName.'.tenant_id', $user->tenant_id)
                        ->orWhereNull($tableName.'.tenant_id');
                });
            }
        }
    }
}
