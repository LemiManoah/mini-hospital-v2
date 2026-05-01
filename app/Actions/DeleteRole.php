<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final readonly class DeleteRole
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(Role $role): void
    {
        if ($role->name === 'super_admin') {
            throw ValidationException::withMessages([
                'role' => 'The super admin role cannot be deleted.',
            ]);
        }

        $actor = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'access',
            event: 'access.role.deleted',
            subject: $role,
            description: 'Role deleted.',
            tenantId: $actor instanceof User ? $actor->tenantId() : null,
            branchId: BranchContext::getActiveBranchId(),
            staffId: $actor instanceof User ? $actor->staffId() : null,
            oldValues: [
                'role_id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
            ],
        );

        $role->delete();
    }
}
