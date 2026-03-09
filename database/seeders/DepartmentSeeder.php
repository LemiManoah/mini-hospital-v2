<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class DepartmentSeeder extends Seeder
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

        $departments = [
            [
                'department_code' => 'CARD',
                'department_name' => 'Cardiology',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-001', 'email' => 'cardiology@hospital.com'],
            ],
            [
                'department_code' => 'ORTHO',
                'department_name' => 'Orthopedics',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-002', 'email' => 'orthopedics@hospital.com'],
            ],
            [
                'department_code' => 'PEDS',
                'department_name' => 'Pediatrics',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-003', 'email' => 'pediatrics@hospital.com'],
            ],
            [
                'department_code' => 'MED',
                'department_name' => 'Internal Medicine',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-004', 'email' => 'medicine@hospital.com'],
            ],
            [
                'department_code' => 'SURG',
                'department_name' => 'Surgery',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-005', 'email' => 'surgery@hospital.com'],
            ],
            [
                'department_code' => 'NURS',
                'department_name' => 'Nursing',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-006', 'email' => 'nursing@hospital.com'],
            ],
            [
                'department_code' => 'LAB',
                'department_name' => 'Laboratory',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-007', 'email' => 'lab@hospital.com'],
            ],
            [
                'department_code' => 'RAD',
                'department_name' => 'Radiology',
                'is_clinical' => true,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-008', 'email' => 'radiology@hospital.com'],
            ],
            [
                'department_code' => 'HR',
                'department_name' => 'Human Resources',
                'is_clinical' => false,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-009', 'email' => 'hr@hospital.com'],
            ],
            [
                'department_code' => 'FIN',
                'department_name' => 'Finance',
                'is_clinical' => false,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-010', 'email' => 'finance@hospital.com'],
            ],
            [
                'department_code' => 'ADMIN',
                'department_name' => 'Administration',
                'is_clinical' => false,
                'is_active' => true,
                'contact_info' => ['phone' => '+256-702-123-011', 'email' => 'admin@hospital.com'],
            ],
        ];

        foreach ($departments as $department) {
            Department::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'department_code' => $department['department_code']],
                array_merge($department, ['tenant_id' => $tenant->id]),
            );
        }
    }
}
