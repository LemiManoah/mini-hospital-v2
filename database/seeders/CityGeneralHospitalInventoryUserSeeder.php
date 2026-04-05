<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffType;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class CityGeneralHospitalInventoryUserSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $mainBranch = $tenant ? $this->cityGeneralMainBranch($tenant) : null;

        if ($tenant === null || $mainBranch === null) {
            return;
        }

        $position = $this->findPosition($tenant, ['Healthcare Administrator', 'Pharmacy Technician']);
        $department = $this->findDepartment($tenant, 'Administration');

        $staff = Staff::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => 'storekeeper@citygeneral.ug',
            ],
            [
                'employee_number' => 'CGH-STORE-001',
                'first_name' => 'Moses',
                'last_name' => 'Kato',
                'phone' => '+256 701 330001',
                'staff_position_id' => $position?->id,
                'type' => StaffType::ADMINISTRATIVE->value,
                'specialty' => 'Inventory Operations',
                'hire_date' => now()->subMonths(4)->toDateString(),
                'is_active' => true,
            ],
        );

        $staff->branches()->sync([
            $mainBranch->id => ['is_primary_location' => true],
        ]);

        if ($department !== null) {
            $staff->departments()->syncWithoutDetaching([$department->id]);
        }

        $user = User::query()->updateOrCreate(
            [
                'email' => 'storekeeper@citygeneral.ug',
            ],
            [
                'tenant_id' => $tenant->id,
                'staff_id' => $staff->id,
                'password' => Hash::make('password'),
                'is_support' => false,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['store_keeper']);
    }
}
