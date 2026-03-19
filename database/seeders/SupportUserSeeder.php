<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class SupportUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or repair the internal support user account.
        $user = User::query()->firstOrNew([
            'email' => 'support@mini-hospital.com',
        ]);

        $user->forceFill([
            'password' => $user->exists ? $user->password : Hash::make('password'),
            'is_support' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles(['admin']);
    }
}
