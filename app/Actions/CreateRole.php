<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateRole
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $permissions
     */
    public function handle(array $attributes, array $permissions = []): Role
    {
        return DB::transaction(function () use ($attributes, $permissions): Role {
            $role = Role::query()->create([
                'name' => $attributes['name'],
                'guard_name' => 'web',
            ]);

            if ($permissions !== []) {
                $role->syncPermissions($permissions);
            }

            $actor = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'access',
                event: 'access.role.created',
                subject: $role,
                description: 'Role created.',
                tenantId: $actor instanceof User ? $actor->tenantId() : null,
                branchId: BranchContext::getActiveBranchId(),
                staffId: $actor instanceof User ? $actor->staffId() : null,
                newValues: [
                    'role_id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions()->pluck('name')->values()->all(),
                ],
            );

            return $role;
        });
    }
}
