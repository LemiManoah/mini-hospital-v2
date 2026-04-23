<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\User\UpdateUserDTO;
use App\Models\User;

final readonly class UpdateUser
{
    public function handle(User $user, UpdateUserDTO $data): void
    {
        $emailChanged = $user->email !== $data->email;

        $user->update([
            'email' => $data->email,
            ...($emailChanged ? ['email_verified_at' => null] : []),
        ]);

        if ($data->roles !== null) {
            $user->syncRoles($data->roles);
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
