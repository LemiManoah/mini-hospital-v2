<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FacilityLevel;
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
        $package = SubscriptionPackage::query()->first();
        $country = Country::query()->where('country_code', 'UG')->first() ?? Country::query()->first();
        $currency = Currency::query()->where('code', 'UGX')->first() ?? Currency::query()->first();

        if (! $package || ! $country || ! $currency) {
            return;
        }

        $tenant = Tenant::query()->firstOrCreate(
            ['domain' => 'qroo'],
            [
                'name' => 'Qroo Medical Center',
                'subscription_package_id' => $package->id,
                'status' => GeneralStatus::ACTIVE,
                'country_id' => $country->id,
                'facility_level' => FacilityLevel::HOSPITAL->value,
            ],
        );

        $branch = FacilityBranch::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'branch_code' => 'QMC-MAIN'],
            [
                'name' => 'Main Branch',
                'currency_id' => $currency->id,
                'status' => GeneralStatus::ACTIVE,
                'is_main_branch' => true,
            ],
        );

        $position = StaffPosition::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'System Administrator'],
            [
                'description' => 'System-wide administrator',
                'is_active' => true,
            ],
        );

        $staff = Staff::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@qroomedical.ug'],
            [
                'employee_number' => 'QMC-ADM-001',
                'first_name' => 'Qroo',
                'last_name' => 'Administrator',
                'staff_position_id' => $position->id,
                'type' => StaffType::ADMINISTRATIVE,
                'hire_date' => now(),
                'is_active' => true,
            ],
        );

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@qroomedical.ug'],
            [
                'tenant_id' => $tenant->id,
                'staff_id' => $staff->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole('admin');

        $staff->branches()->syncWithoutDetaching([$branch->id => ['is_primary_location' => true]]);
    }
}
