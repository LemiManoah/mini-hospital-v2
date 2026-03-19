<?php

declare(strict_types = 1)
;

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

final class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return ($user->is_support || $user->hasRole('super_admin') || $user->hasRole('admin'))
            && $user->hasPermissionTo('tenants.view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return ($user->is_support || $user->hasRole('super_admin'))
            && $user->hasPermissionTo('tenants.update');
    }

    public function onboard(User $user, Tenant $tenant): bool
    {
        if ($user->tenant_id === $tenant->id && $user->hasPermissionTo('tenants.onboard')) {
            return true;
        }

        return $this->update($user, $tenant);
    }

    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        if ($user->tenant_id === $tenant->id && $user->hasPermissionTo('tenants.manage_subscription')) {
            return true;
        }

        return $this->update($user, $tenant);
    }
}
