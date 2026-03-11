<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GeneralStatus;
use App\Models\Address;
use App\Models\Clinic;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ClinicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        $branch = FacilityBranch::where('tenant_id', $tenant->id)->first() ?? FacilityBranch::factory()->create(['tenant_id' => $tenant->id]);
        $department = Department::where('tenant_id', $tenant->id)->first() ?? Department::factory()->create(['tenant_id' => $tenant->id]);
        $address = Address::first() ?? Address::factory()->create();

        $clinics = [
            ['name' => 'General OPD', 'code' => 'GOPD-01'],
            ['name' => 'ENT Clinic', 'code' => 'ENT-01'],
            ['name' => 'Dental Clinic', 'code' => 'DEN-01'],
            ['name' => 'Eye Clinic', 'code' => 'EYE-01'],
            ['name' => 'Mother & Child Clinic', 'code' => 'MCH-01'],
            ['name' => 'Physiotherapy', 'code' => 'PHYS-01'],
        ];

        foreach ($clinics as $clinic) {
            Clinic::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'clinic_code' => $clinic['code'],
                ],
                [
                    'id' => (string) Str::uuid(),
                    'branch_id' => $branch->id,
                    'clinic_name' => $clinic['name'],
                    'department_id' => $department->id,
                    'address_id' => $address->id,
                    'status' => GeneralStatus::ACTIVE,
                ]
            );
        }
    }
}
