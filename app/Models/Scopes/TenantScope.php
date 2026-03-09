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

            if ($user->tenant_id !== null) {
                $builder->where(function ($query) use ($model, $user): void {
                    $query->where($model->getTable().'.tenant_id', $user->tenant_id)
                        ->orWhereNull($model->getTable().'.tenant_id');
                });
            }
        }
    }
}
