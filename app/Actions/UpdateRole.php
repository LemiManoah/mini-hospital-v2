<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateRole
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $permissions
     */
    public function handle(Role $role, array $attributes, array $permissions = []): Role
    {
        return DB::transaction(function () use ($role, $attributes, $permissions): Role {
            $oldName = $role->name;
            $oldPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

            $role->update([
                'name' => $attributes['name'],
            ]);

            $role->syncPermissions($permissions);

            $newPermissions = $role->permissions()->pluck('name')->sort()->values()->all();
            $actor = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'access',
                event: 'access.role.updated',
                subject: $role,
                description: 'Role updated.',
                tenantId: $actor instanceof User ? $actor->tenantId() : null,
                branchId: BranchContext::getActiveBranchId(),
                staffId: $actor instanceof User ? $actor->staffId() : null,
                oldValues: [
                    'name' => $oldName,
                ],
                newValues: [
                    'name' => $role->name,
                ],
            );

            if ($oldPermissions !== $newPermissions) {
                $this->recordAuditActivity->handle(
                    logName: 'access',
                    event: 'access.role.permissions_changed',
                    subject: $role,
                    description: 'Role permissions changed.',
                    tenantId: $actor instanceof User ? $actor->tenantId() : null,
                    branchId: BranchContext::getActiveBranchId(),
                    staffId: $actor instanceof User ? $actor->staffId() : null,
                    oldValues: [
                        'permissions' => $oldPermissions,
                    ],
                    newValues: [
                        'permissions' => $newPermissions,
                    ],
                );
            }

            return $role;
        });
    }
}
