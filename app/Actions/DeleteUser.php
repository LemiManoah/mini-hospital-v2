<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;

final readonly class DeleteUser
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(User $user): void
    {
        $actor = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'access',
            event: 'access.user.deleted',
            subject: $user,
            description: 'User account deleted.',
            tenantId: $user->tenant_id ?? ($actor instanceof User ? $actor->tenantId() : null),
            branchId: BranchContext::getActiveBranchId(),
            staffId: $actor instanceof User ? $actor->staffId() : null,
            oldValues: [
                'user_id' => $user->id,
                'email' => $user->email,
                'staff_id' => $user->staff_id,
                'roles' => $user->roles()->pluck('name')->values()->all(),
            ],
        );

        $user->delete();
    }
}
