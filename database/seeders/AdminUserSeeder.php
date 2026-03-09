<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get Dependencies
        $package = SubscriptionPackage::query()->first();
        $country = Country::query()->where('country_code', 'UG')->first() ?? Country::query()->first();
        $currency = Currency::query()->where('code', 'UGX')->first() ?? Currency::query()->first();

        if (! $package || ! $country || ! $currency) {
            return;
        }

        // 2. Create Tenant
        $tenant = Tenant::query()->firstOrCreate(
            ['name' => 'Default Hospital'],
            [
                'domain' => 'default',
                'subscription_package_id' => $package->id,
                'status' => GeneralStatus::ACTIVE,
                'country_id' => $country->id,
            ]
        );

        // 3. Create Branch
        $branch = FacilityBranch::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'branch_code' => 'MAIN'],
            [
                'name' => 'Main Branch',
                'currency_id' => $currency->id,
                'status' => GeneralStatus::ACTIVE,
                'is_main_branch' => true,
            ]
        );

        // 4. Create Staff Position
        $position = StaffPosition::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'System Administrator'],
            [
                'description' => 'System-wide administrator',
                'is_active' => true,
            ]
        );

        // 5. Create Staff
        $staff = Staff::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@hospital.com'],
            [
                'employee_number' => 'ADM-001',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'staff_position_id' => $position->id,
                'type' => StaffType::ADMINISTRATIVE,
                'hire_date' => now(),
                'is_active' => true,
            ]
        );

        // 6. Create User
        $user = User::query()->firstOrCreate(
            ['email' => 'admin@hospital.com'],
            [
                'tenant_id' => $tenant->id,
                'staff_id' => $staff->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 7. Assign Role
        $user->assignRole('admin');

        // Link staff to branch
        $staff->branches()->syncWithoutDetaching([$branch->id => ['is_primary_location' => true]]);
    }
}
