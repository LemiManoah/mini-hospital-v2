<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();
        if (! $tenant) {
            return;
        }

        $branch = FacilityBranch::query()->where('tenant_id', $tenant->id)->first();
        $departments = Department::query()->where('tenant_id', $tenant->id)->take(3)->get()->values();

        if (! $branch instanceof FacilityBranch || $departments->count() < 3) {
            return;
        }

        $generalDepartment = $departments->get(0);
        $dentalDepartment = $departments->get(1);
        $eyeDepartment = $departments->get(2);

        if (
            ! $generalDepartment instanceof Department
            || ! $dentalDepartment instanceof Department
            || ! $eyeDepartment instanceof Department
        ) {
            return;
        }

        $clinics = [
            [
                'clinic_name' => 'General OPD',
                'clinic_code' => 'OPD-01',
                'department_id' => $generalDepartment->id,
                'location' => 'Main Wing, Ground Floor',
                'phone' => '+1234567890',
            ],
            [
                'clinic_name' => 'Dental Clinic',
                'clinic_code' => 'DENT-01',
                'department_id' => $dentalDepartment->id,
                'location' => 'Block B, 1st Floor',
                'phone' => '+1234567891',
            ],
            [
                'clinic_name' => 'Eye Clinic',
                'clinic_code' => 'EYE-01',
                'department_id' => $eyeDepartment->id,
                'location' => 'Block C, 2nd Floor',
                'phone' => '+1234567892',
            ],
        ];

        foreach ($clinics as $clinicData) {
            Clinic::query()->updateOrCreate([
                'tenant_id' => $tenant->id,
                'clinic_code' => $clinicData['clinic_code'],
            ], array_merge($clinicData, [
                'branch_id' => $branch->id,
                'status' => 'active',
            ]));
        }
    }
}
