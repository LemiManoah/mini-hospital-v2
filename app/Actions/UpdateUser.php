<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): void
    {
        $emailChanged = isset($attributes['email']) && $user->email !== $attributes['email'];

        $roles = $attributes['roles'] ?? null;
        unset($attributes['roles']);

        $user->update([
            ...$attributes,
            ...($emailChanged ? ['email_verified_at' => null] : []),
        ]);

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
