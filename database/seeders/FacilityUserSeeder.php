<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffType;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class FacilityUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUser(
            tenantDomain: 'qroo',
            role: 'receptionist',
            email: 'reception@qroomedical.ug',
            employeeNumber: 'QMC-REC-001',
            firstName: 'Diana',
            lastName: 'Nakato',
            phone: '+256 700 100001',
            positionNames: ['Healthcare Administrator'],
            departmentName: 'Administration',
            type: StaffType::ADMINISTRATIVE,
            specialty: 'Front Desk Operations',
        );

        $this->seedUser(
            tenantDomain: 'qroo',
            role: 'cashier',
            email: 'cashier@qroomedical.ug',
            employeeNumber: 'QMC-CSH-001',
            firstName: 'Peter',
            lastName: 'Ssenfuma',
            phone: '+256 700 100002',
            positionNames: ['Finance Officer', 'Healthcare Administrator'],
            departmentName: 'Finance',
            type: StaffType::ADMINISTRATIVE,
            specialty: 'OPD Cash Collections',
        );

        $this->seedUser(
            tenantDomain: 'qroo',
            role: 'accountant',
            email: 'accounts@qroomedical.ug',
            employeeNumber: 'QMC-ACC-001',
            firstName: 'Rita',
            lastName: 'Namirembe',
            phone: '+256 700 100003',
            positionNames: ['Finance Officer', 'Healthcare Administrator'],
            departmentName: 'Finance',
            type: StaffType::ADMINISTRATIVE,
            specialty: 'Revenue and Receivables',
        );

        $this->seedUser(
            tenantDomain: 'qroo',
            role: 'doctor',
            email: 'dr.grace.namara@qroomedical.ug',
            employeeNumber: 'QMC-DR-001',
            firstName: 'Grace',
            lastName: 'Namara',
            phone: '+256 701 210001',
            positionNames: ['Consultant Doctor', 'Doctor'],
            departmentName: 'Internal Medicine',
            type: StaffType::MEDICAL,
            specialty: 'Family Medicine',
        );

        $this->seedUser(
            tenantDomain: 'kigaliheights',
            role: 'receptionist',
            email: 'reception@kigaliheights.rw',
            employeeNumber: 'KHRH-REC-001',
            firstName: 'Aline',
            lastName: 'Mukamana',
            phone: '+250 788 200001',
            positionNames: ['Healthcare Administrator'],
            departmentName: 'Administration',
            type: StaffType::ADMINISTRATIVE,
            specialty: 'Front Desk Operations',
        );

        $this->seedUser(
            tenantDomain: 'kigaliheights',
            role: 'cashier',
            email: 'cashier@kigaliheights.rw',
            employeeNumber: 'KHRH-CSH-001',
            firstName: 'Jean',
            lastName: 'Uwase',
            phone: '+250 788 200002',
            positionNames: ['Finance Officer', 'Healthcare Administrator'],
            departmentName: 'Finance',
            type: StaffType::ADMINISTRATIVE,
            specialty: 'OPD Cash Collections',
        );

        $this->seedUser(
            tenantDomain: 'nairoimedical',
            role: 'doctor',
            email: 'doctor@nairoimedical.ke',
            employeeNumber: 'NMC-DR-001',
            firstName: 'Mercy',
            lastName: 'Wanjiku',
            phone: '+254 20 900001',
            positionNames: ['Doctor', 'Medical Officer'],
            departmentName: 'Internal Medicine',
            type: StaffType::MEDICAL,
            specialty: 'General Practice',
        );

        $this->seedUser(
            tenantDomain: 'nairoimedical',
            role: 'accountant',
            email: 'accounts@nairoimedical.ke',
            employeeNumber: 'NMC-ACC-001',
            firstName: 'David',
            lastName: 'Otieno',
            phone: '+254 20 900002',
            positionNames: ['Finance Officer', 'Healthcare Administrator'],
            departmentName: 'Finance',
            type: StaffType::ADMINISTRATIVE,
            specialty: 'Revenue and Receivables',
        );
    }

    /**
     * @param  list<string>  $positionNames
     */
    private function seedUser(
        string $tenantDomain,
        string $role,
        string $email,
        string $employeeNumber,
        string $firstName,
        string $lastName,
        string $phone,
        array $positionNames,
        string $departmentName,
        StaffType $type,
        string $specialty,
    ): void {
        $tenant = Tenant::query()
            ->where('domain', $tenantDomain)
            ->first();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $branch = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_main_branch')
            ->orderBy('name')
            ->first();

        if (! $branch instanceof FacilityBranch) {
            return;
        }

        $position = null;

        foreach ($positionNames as $positionName) {
            $position = StaffPosition::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $positionName)
                ->first();

            if ($position instanceof StaffPosition) {
                break;
            }
        }

        $department = Department::query()
            ->where('tenant_id', $tenant->id)
            ->where('department_name', $departmentName)
            ->first();

        $staff = Staff::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => $email,
            ],
            [
                'employee_number' => $employeeNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'staff_position_id' => $position?->id,
                'type' => $type->value,
                'specialty' => $specialty,
                'hire_date' => now()->subMonths(3)->toDateString(),
                'is_active' => true,
            ],
        );

        $staff->branches()->syncWithoutDetaching([
            $branch->id => ['is_primary_location' => true],
        ]);

        if ($department instanceof Department) {
            $staff->departments()->syncWithoutDetaching([$department->id]);
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenant->id,
                'staff_id' => $staff->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role]);
    }
}
