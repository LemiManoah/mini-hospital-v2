<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\User\CreateUserDTO;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;

final readonly class CreateUser
{
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

            return $user;
        });
    }
}
