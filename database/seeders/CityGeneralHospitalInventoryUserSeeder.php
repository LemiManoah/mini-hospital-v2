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

        $this->createInventoryUser(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            email: 'storekeeper@citygeneral.ug',
            employeeNumber: 'CGH-INV-STORE-001',
            firstName: 'Moses',
            lastName: 'Kato',
            phone: '+256 701 330001',
            role: 'store_keeper',
            specialty: 'Inventory Operations',
            positionId: $this->findPosition($tenant, ['Healthcare Administrator', 'Pharmacy Technician'])?->id,
            departmentId: $this->findDepartment($tenant, 'Administration')?->id,
            type: StaffType::ADMINISTRATIVE,
        );

        $this->createInventoryUser(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            email: 'pharmacy@citygeneral.ug',
            employeeNumber: 'CGH-INV-PHARM-001',
            firstName: 'Sarah',
            lastName: 'Namusoke',
            phone: '+256 701 330002',
            role: 'pharmacist',
            specialty: 'Pharmacy Inventory',
            positionId: $this->findPosition($tenant, ['Pharmacist', 'Pharmacy Technician'])?->id,
            departmentId: $this->findDepartment($tenant, 'Pharmacy')?->id,
            type: StaffType::ALLIED_HEALTH,
        );

        $this->createInventoryUser(
            tenantId: $tenant->id,
            branchId: $mainBranch->id,
            email: 'lab@citygeneral.ug',
            employeeNumber: 'CGH-INV-LAB-001',
            firstName: 'Brian',
            lastName: 'Ssemanda',
            phone: '+256 701 330003',
            role: 'lab_technician',
            specialty: 'Laboratory Stock Control',
            positionId: $this->findPosition($tenant, ['Laboratory Technician', 'Lab Technician'])?->id,
            departmentId: $this->findDepartment($tenant, 'Laboratory')?->id,
            type: StaffType::ALLIED_HEALTH,
        );
    }

    private function createInventoryUser(
        string $tenantId,
        string $branchId,
        string $email,
        string $employeeNumber,
        string $firstName,
        string $lastName,
        string $phone,
        string $role,
        string $specialty,
        ?string $positionId,
        ?string $departmentId,
        StaffType $type,
    ): void {
        $staff = Staff::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'email' => $email,
            ],
            [
                'email' => $email,
                'employee_number' => $employeeNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'staff_position_id' => $positionId,
                'type' => $type->value,
                'specialty' => $specialty,
                'hire_date' => now()->subMonths(4)->toDateString(),
                'is_active' => true,
            ],
        );

        $staff->branches()->sync([
            $branchId => ['is_primary_location' => true],
        ]);

        if ($departmentId !== null) {
            $staff->departments()->syncWithoutDetaching([$departmentId]);
        }

        $user = User::query()->updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'tenant_id' => $tenantId,
                'staff_id' => $staff->id,
                'password' => Hash::make('password'),
                'is_support' => false,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role]);
    }
}
