<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StaffPosition;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class StaffPositionSeeder extends Seeder
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

        $positions = [
            [
                'name' => 'Chief Medical Officer',
                'description' => 'Oversees all medical operations and clinical staff',
                'is_active' => true,
            ],
            [
                'name' => 'Consultant Doctor',
                'description' => 'Senior medical practitioner with specialized expertise',
                'is_active' => true,
            ],
            [
                'name' => 'Doctor',
                'description' => 'Medical doctor providing patient care',
                'is_active' => true,
            ],
            [
                'name' => 'Medical Officer',
                'description' => 'Medical professional in training or support role',
                'is_active' => true,
            ],
            [
                'name' => 'Head Nurse',
                'description' => 'Senior nursing professional managing nursing staff',
                'is_active' => true,
            ],
            [
                'name' => 'Senior Nurse',
                'description' => 'Experienced nurse with supervisory responsibilities',
                'is_active' => true,
            ],
            [
                'name' => 'Registered Nurse',
                'description' => 'Qualified nurse providing patient care',
                'is_active' => true,
            ],
            [
                'name' => 'Nursing Assistant',
                'description' => 'Support staff assisting qualified nurses',
                'is_active' => true,
            ],
            [
                'name' => 'Laboratory Technician',
                'description' => 'Conducts laboratory tests and analysis',
                'is_active' => true,
            ],
            [
                'name' => 'Radiographer',
                'description' => 'Operates radiological equipment and imaging services',
                'is_active' => true,
            ],
            [
                'name' => 'Pharmacist',
                'description' => 'Manages medications and pharmaceutical services',
                'is_active' => true,
            ],
            [
                'name' => 'Pharmacy Technician',
                'description' => 'Assists pharmacist in medication dispensing',
                'is_active' => true,
            ],
            [
                'name' => 'Healthcare Administrator',
                'description' => 'Manages administrative and operational functions',
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources Officer',
                'description' => 'Manages staff recruitment and HR matters',
                'is_active' => true,
            ],
            [
                'name' => 'Finance Officer',
                'description' => 'Handles financial management and budgeting',
                'is_active' => true,
            ],
            [
                'name' => 'IT Support Officer',
                'description' => 'Provides technical support and IT services',
                'is_active' => true,
            ],
            [
                'name' => 'System Administrator',
                'description' => 'System-wide administrator',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {
            StaffPosition::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $position['name']],
                array_merge($position, ['tenant_id' => $tenant->id]),
            );
        }
    }
}
