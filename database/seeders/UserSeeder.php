<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create default tenant
        $tenant = Tenant::query()->firstOrCreate(
            ['name' => 'Default Hospital'],
        );

        // Get all staff members
        $staffMembers = Staff::query()
            ->where('tenant_id', $tenant->id)
            ->get();

        foreach ($staffMembers as $staff) {
            // Create user account for each staff member
            User::query()->firstOrCreate(
                ['email' => $staff->email],
                [
                    'tenant_id' => $tenant->id,
                    'staff_id' => $staff->id,
                    'password' => Hash::make('password123'), // Default password
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
