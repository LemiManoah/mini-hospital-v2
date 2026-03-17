<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StaffPosition;
use App\Models\Tenant;

final class EnsureTenantStaffPositions
{
    public function handle(Tenant $tenant): void
    {
        $positions = [
            [
                'name' => 'Chief Medical Officer',
                'description' => 'Oversees all medical operations and clinical staff',
            ],
            [
                'name' => 'Consultant Doctor',
                'description' => 'Senior medical practitioner with specialized expertise',
            ],
            [
                'name' => 'Doctor',
                'description' => 'Medical doctor providing patient care',
            ],
            [
                'name' => 'Medical Officer',
                'description' => 'Medical professional in training or support role',
            ],
            [
                'name' => 'Head Nurse',
                'description' => 'Senior nursing professional managing nursing staff',
            ],
            [
                'name' => 'Senior Nurse',
                'description' => 'Experienced nurse with supervisory responsibilities',
            ],
            [
                'name' => 'Registered Nurse',
                'description' => 'Qualified nurse providing patient care',
            ],
            [
                'name' => 'Nursing Assistant',
                'description' => 'Support staff assisting qualified nurses',
            ],
            [
                'name' => 'Laboratory Technician',
                'description' => 'Conducts laboratory tests and analysis',
            ],
            [
                'name' => 'Radiographer',
                'description' => 'Operates radiological equipment and imaging services',
            ],
            [
                'name' => 'Pharmacist',
                'description' => 'Manages medications and pharmaceutical services',
            ],
            [
                'name' => 'Pharmacy Technician',
                'description' => 'Assists pharmacist in medication dispensing',
            ],
            [
                'name' => 'Healthcare Administrator',
                'description' => 'Manages administrative and operational functions',
            ],
            [
                'name' => 'Human Resources Officer',
                'description' => 'Manages staff recruitment and HR matters',
            ],
            [
                'name' => 'Finance Officer',
                'description' => 'Handles financial management and budgeting',
            ],
            [
                'name' => 'IT Support Officer',
                'description' => 'Provides technical support and IT services',
            ],
            [
                'name' => 'System Administrator',
                'description' => 'System-wide administrator',
            ],
        ];

        foreach ($positions as $position) {
            StaffPosition::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $position['name'],
                ],
                [
                    ...$position,
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ],
            );
        }
    }
}
