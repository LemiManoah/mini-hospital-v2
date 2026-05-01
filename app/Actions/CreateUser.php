<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\User\CreateUserDTO;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateUser
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(CreateUserDTO $data, #[SensitiveParameter] string $password): User
    {
        return DB::transaction(function () use ($data, $password): User {
            $user = User::query()->create([
                'staff_id' => $data->staffId,
                'email' => $data->email,
                'password' => $password,
            ]);

            $user->syncRoles($data->roles);

            event(new Registered($user));

            $actor = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'access',
                event: 'access.user.created',
                subject: $user,
                description: 'User account created.',
                tenantId: $user->tenant_id ?? ($actor instanceof User ? $actor->tenantId() : null),
                branchId: BranchContext::getActiveBranchId(),
                staffId: $actor instanceof User ? $actor->staffId() : null,
                newValues: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'staff_id' => $user->staff_id,
                    'roles' => $user->roles->pluck('name')->values()->all(),
                ],
            );

            return $user;
        });
    }
}
