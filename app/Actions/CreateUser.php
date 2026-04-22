<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateUser
{
    /**
     * @param  array{roles?: list<string>} & array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
    {
        return DB::transaction(function () use ($attributes, $password): User {
            /** @var list<string> $roles */
            $roles = $attributes['roles'] ?? [];
            unset($attributes['roles']);

            $user = User::query()->create([
                ...$attributes,
                'password' => $password,
            ]);

            $user->syncRoles($roles);

            event(new Registered($user));

            return $user;
        });
    }
}
