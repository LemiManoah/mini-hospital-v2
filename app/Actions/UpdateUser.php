<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\User\UpdateUserDTO;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateUser
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(User $user, UpdateUserDTO $data): void
    {
        $emailChanged = $user->email !== $data->email;
        $oldEmail = $user->email;
        $oldRoles = $user->roles()->pluck('name')->values()->all();

        $user->update([
            'email' => $data->email,
            ...($emailChanged ? ['email_verified_at' => null] : []),
        ]);

        if ($data->roles !== null) {
            $user->syncRoles($data->roles);
        }

        $newRoles = $user->roles()->pluck('name')->values()->all();
        $actor = Auth::user();

        $this->recordAuditActivity->handle(
            logName: 'access',
            event: 'access.user.updated',
            subject: $user,
            description: 'User account updated.',
            tenantId: $user->tenant_id ?? ($actor instanceof User ? $actor->tenantId() : null),
            branchId: BranchContext::getActiveBranchId(),
            staffId: $actor instanceof User ? $actor->staffId() : null,
            oldValues: [
                'email' => $oldEmail,
            ],
            newValues: [
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ],
        );

        if ($oldRoles !== $newRoles) {
            $this->recordAuditActivity->handle(
                logName: 'access',
                event: 'access.user.roles_changed',
                subject: $user,
                description: 'User roles changed.',
                tenantId: $user->tenant_id ?? ($actor instanceof User ? $actor->tenantId() : null),
                branchId: BranchContext::getActiveBranchId(),
                staffId: $actor instanceof User ? $actor->staffId() : null,
                oldValues: [
                    'roles' => $oldRoles,
                ],
                newValues: [
                    'roles' => $newRoles,
                ],
            );
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
