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
        // Create a super admin user for system administration
        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@mini-hospital.com'],
            [
                'password' => Hash::make('password'),
                'is_support' => true,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Create a support user for internal company use
        $supportUser = User::query()->firstOrCreate(
            ['email' => 'support@mini-hospital.com'],
            [
                'password' => Hash::make('password'),
                'is_support' => true,
                'email_verified_at' => now(),
            ]
        );

        $supportUser->assignRole('admin');
    }
}
