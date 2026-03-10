<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StaffType;
use App\Models\Address;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class StaffSeeder extends Seeder
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

        // Get main branch or create one
        $branch = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $branch) {
            return; // FacilityBranch must exist before creating staff
        }

        // Get sample addresses
        $addresses = Address::query()->inRandomOrder()->take(10)->get();

        if ($addresses->isEmpty()) {
            return; // Addresses must be seeded first
        }

        $staffData = [
            [
                'employee_number' => 'MED-001',
                'first_name' => 'Dr. John',
                'last_name' => 'Okonkwo',
                'middle_name' => 'James',
                'email' => 'john.okonkwo@hospital.com',
                'phone' => '+256-702-111-001',
                'department_name' => 'Cardiology',
                'position_name' => 'Consultant Doctor',
                'type' => StaffType::MEDICAL,
                'license_number' => 'MED-UG-2023-001',
                'specialty' => 'Cardiology',
            ],
            [
                'employee_number' => 'MED-002',
                'first_name' => 'Dr. Sarah',
                'last_name' => 'Mutesi',
                'middle_name' => 'Anne',
                'email' => 'sarah.mutesi@hospital.com',
                'phone' => '+256-702-111-002',
                'department_name' => 'Orthopedics',
                'position_name' => 'Doctor',
                'type' => StaffType::MEDICAL,
                'license_number' => 'MED-UG-2023-002',
                'specialty' => 'Orthopedic Surgery',
            ],
            [
                'employee_number' => 'MED-003',
                'first_name' => 'Dr. Robert',
                'last_name' => 'Kato',
                'middle_name' => 'Paul',
                'email' => 'robert.kato@hospital.com',
                'phone' => '+256-702-111-003',
                'department_name' => 'Pediatrics',
                'position_name' => 'Consultant Doctor',
                'type' => StaffType::MEDICAL,
                'license_number' => 'MED-UG-2023-003',
                'specialty' => 'Pediatrics',
            ],
            [
                'employee_number' => 'MED-004',
                'first_name' => 'Dr. Mary',
                'last_name' => 'Namayanja',
                'middle_name' => 'Grace',
                'email' => 'mary.namayanja@hospital.com',
                'phone' => '+256-702-111-004',
                'department_name' => 'Internal Medicine',
                'position_name' => 'Doctor',
                'type' => StaffType::MEDICAL,
                'license_number' => 'MED-UG-2023-004',
                'specialty' => 'Internal Medicine',
            ],
            [
                'employee_number' => 'SUR-001',
                'first_name' => 'Dr. James',
                'last_name' => 'Kyambire',
                'middle_name' => 'Moses',
                'email' => 'james.kyambire@hospital.com',
                'phone' => '+256-702-111-005',
                'department_name' => 'Surgery',
                'position_name' => 'Consultant Doctor',
                'type' => StaffType::MEDICAL,
                'license_number' => 'MED-UG-2023-005',
                'specialty' => 'General Surgery',
            ],
            [
                'employee_number' => 'NUR-001',
                'first_name' => 'Jane',
                'last_name' => 'Apio',
                'middle_name' => 'Catherine',
                'email' => 'jane.apio@hospital.com',
                'phone' => '+256-702-111-006',
                'department_name' => 'Nursing',
                'position_name' => 'Head Nurse',
                'type' => StaffType::NURSING,
                'specialty' => 'Nursing Administration',
            ],
            [
                'employee_number' => 'NUR-002',
                'first_name' => 'Peter',
                'last_name' => 'Okina',
                'middle_name' => 'Victor',
                'email' => 'peter.okina@hospital.com',
                'phone' => '+256-702-111-007',
                'department_name' => 'Nursing',
                'position_name' => 'Senior Nurse',
                'type' => StaffType::NURSING,
                'specialty' => 'Emergency Medicine',
            ],
            [
                'employee_number' => 'NUR-003',
                'first_name' => 'Alice',
                'last_name' => 'Mukwaya',
                'middle_name' => 'Beatrice',
                'email' => 'alice.mukwaya@hospital.com',
                'phone' => '+256-702-111-008',
                'department_name' => 'Nursing',
                'position_name' => 'Registered Nurse',
                'type' => StaffType::NURSING,
                'specialty' => 'General Nursing',
            ],
            [
                'employee_number' => 'LAB-001',
                'first_name' => 'David',
                'last_name' => 'Ssemanda',
                'middle_name' => 'Leon',
                'email' => 'david.ssemanda@hospital.com',
                'phone' => '+256-702-111-009',
                'department_name' => 'Laboratory',
                'position_name' => 'Laboratory Technician',
                'type' => StaffType::ALLIED_HEALTH,
                'specialty' => 'Clinical Laboratory',
            ],
            [
                'employee_number' => 'RAD-001',
                'first_name' => 'Elizabeth',
                'last_name' => 'Nabugumira',
                'middle_name' => 'Joan',
                'email' => 'elizabeth.nabugumira@hospital.com',
                'phone' => '+256-702-111-010',
                'department_name' => 'Radiology',
                'position_name' => 'Radiographer',
                'type' => StaffType::ALLIED_HEALTH,
                'specialty' => 'Medical Imaging',
            ],
            [
                'employee_number' => 'HAD-001',
                'first_name' => 'Michael',
                'last_name' => 'Wakiro',
                'middle_name' => 'David',
                'email' => 'michael.wakiro@hospital.com',
                'phone' => '+256-702-111-011',
                'department_name' => 'Administration',
                'position_name' => 'Healthcare Administrator',
                'type' => StaffType::ADMINISTRATIVE,
                'specialty' => 'Healthcare Management',
            ],
            [
                'employee_number' => 'FIN-001',
                'first_name' => 'Stella',
                'last_name' => 'Senteza',
                'middle_name' => 'Rose',
                'email' => 'stella.senteza@hospital.com',
                'phone' => '+256-702-111-012',
                'department_name' => 'Finance',
                'position_name' => 'Finance Officer',
                'type' => StaffType::ADMINISTRATIVE,
                'specialty' => 'Financial Management',
            ],
            [
                'employee_number' => 'HR-001',
                'first_name' => 'Vincent',
                'last_name' => 'Kyeyune',
                'middle_name' => 'Andrew',
                'email' => 'vincent.kyeyune@hospital.com',
                'phone' => '+256-702-111-013',
                'department_name' => 'Human Resources',
                'position_name' => 'Human Resources Officer',
                'type' => StaffType::ADMINISTRATIVE,
                'specialty' => 'Human Resources',
            ],
            [
                'employee_number' => 'IT-001',
                'first_name' => 'Richard',
                'last_name' => 'Owino',
                'middle_name' => 'Stephen',
                'email' => 'richard.owino@hospital.com',
                'phone' => '+256-702-111-014',
                'department_name' => 'Administration',
                'position_name' => 'IT Support Officer',
                'type' => StaffType::TECHNICAL,
                'specialty' => 'Information Technology',
            ],
        ];

        $addressIndex = 0;

        foreach ($staffData as $data) {
            // Get or find department
            $department = Department::query()
                ->where('tenant_id', $tenant->id)
                ->where('department_name', $data['department_name'])
                ->first();

            if (! $department) {
                continue;
            }

            // Get or find position
            $position = StaffPosition::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $data['position_name'])
                ->first();

            if (! $position) {
                continue;
            }

            // Get address
            $address = $addresses[$addressIndex % $addresses->count()];
            $addressIndex++;

            // Create staff
            $staff = Staff::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $data['email']],
                [
                    'employee_number' => $data['employee_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address_id' => $address->id,
                    'staff_position_id' => $position->id,
                    'type' => $data['type'],
                    'license_number' => $data['license_number'] ?? null,
                    'specialty' => $data['specialty'] ?? null,
                    'hire_date' => now()->subMonths(random_int(3, 60)),
                    'is_active' => true,
                    'tenant_id' => $tenant->id,
                ],
            );

            // Link staff to branch
            $staff->branches()->syncWithoutDetaching([$branch->id => ['is_primary_location' => true]]);
            $staff->departments()->syncWithoutDetaching([$department->id]);
        }
    }
}
