<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notification;

final readonly class NotifyUsersWithPermission
{
    /**
     * Send a notification to all active users in a tenant who hold the given permission.
     * Optionally scope to a specific branch via branch_id on the user's active_branch.
     *
     * @param  list<string>  $permissions  At least one permission must match (OR logic).
     */
    public function handle(string $tenantId, array $permissions, Notification $notification): void
    {
        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($permissions): void {
                foreach ($permissions as $permission) {
                    $query->orWhereHas('roles.permissions', function (Builder $permissionQuery) use ($permission): void {
                        $permissionQuery->where('name', $permission);
                    })->orWhereHas('permissions', function (Builder $permissionQuery) use ($permission): void {
                        $permissionQuery->where('name', $permission);
                    });
                }
            })
            ->get();

        foreach ($users as $user) {
            $user->notify($notification);
        }
    }
}
