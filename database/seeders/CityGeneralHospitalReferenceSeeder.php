<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FacilityServiceCategory;
use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Models\Clinic;
use App\Models\Country;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\SpecimenType;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\Tenant;
use Database\Seeders\Concerns\InteractsWithCityGeneralHospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class CityGeneralHospitalReferenceSeeder extends Seeder
{
    use InteractsWithCityGeneralHospital;

    public function run(): void
    {
        $tenant = $this->cityGeneralTenant();
        $country = $this->ugandaCountry();

        if (! $tenant instanceof Tenant || ! $country instanceof Country) {
            return;
        }

        $registrar = $this->cityGeneralRegistrar($tenant);
        $supportStaff = $this->cityGeneralSupportStaff($tenant);
        $branches = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('branch_code');

        if ($branches->isEmpty()) {
            return;
        }

        $this->seedStaff($tenant->id, $registrar?->id, $branches);
        $this->seedClinics($tenant->id, $supportStaff?->id, $branches);
        $this->seedFacilityServices($tenant->id, $registrar?->id);
        $this->seedLabCatalog($tenant->id);
    }

    /**
     * @param  Collection<string, FacilityBranch>  $branches
     */
    private function seedStaff(string $tenantId, ?string $userId, Collection $branches): void
    {
        $tenant = $this->cityGeneralTenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $staffBlueprints = [
            [
                'employee_number' => 'CGH-DR-001',
                'first_name' => 'Grace',
                'last_name' => 'Namara',
                'email' => 'dr.grace.namara@citygeneral.ug',
                'phone' => '+256 701 210001',
                'department' => 'Internal Medicine',
                'positions' => ['Consultant Doctor', 'Doctor'],
                'type' => StaffType::MEDICAL,
                'license_number' => 'UGMC-2026-001',
                'specialty' => 'Family Medicine',
                'branch_code' => 'CGH-MAIN',
                'address' => ['city' => 'Kampala', 'district' => 'Nakawa', 'state' => 'Central'],
            ],
            [
                'employee_number' => 'CGH-DR-002',
                'first_name' => 'Samuel',
                'last_name' => 'Kirabo',
                'email' => 'dr.samuel.kirabo@citygeneral.ug',
                'phone' => '+256 701 210002',
                'department' => 'Pediatrics',
                'positions' => ['Doctor', 'Consultant Doctor'],
                'type' => StaffType::MEDICAL,
                'license_number' => 'UGMC-2026-002',
                'specialty' => 'Pediatrics',
                'branch_code' => 'CGH-MAIN',
                'address' => ['city' => 'Kampala', 'district' => 'Makindye', 'state' => 'Central'],
            ],
            [
                'employee_number' => 'CGH-DR-003',
                'first_name' => 'Patricia',
                'last_name' => 'Nalukwago',
                'email' => 'dr.patricia.nalukwago@citygeneral.ug',
                'phone' => '+256 701 210003',
                'department' => 'Surgery',
                'positions' => ['Doctor', 'Consultant Doctor'],
                'type' => StaffType::MEDICAL,
                'license_number' => 'UGMC-2026-003',
                'specialty' => 'General Surgery',
                'branch_code' => 'CGH-ENT',
                'address' => ['city' => 'Entebbe', 'district' => 'Wakiso', 'state' => 'Central'],
            ],
            [
                'employee_number' => 'CGH-NUR-001',
                'first_name' => 'Esther',
                'last_name' => 'Mugerwa',
                'email' => 'esther.mugerwa@citygeneral.ug',
                'phone' => '+256 701 210004',
                'department' => 'Nursing',
                'positions' => ['Head Nurse', 'Senior Nurse'],
                'type' => StaffType::NURSING,
                'license_number' => null,
                'specialty' => 'Outpatient Nursing',
                'branch_code' => 'CGH-MAIN',
                'address' => ['city' => 'Kampala', 'district' => 'Rubaga', 'state' => 'Central'],
            ],
            [
                'employee_number' => 'CGH-NUR-002',
                'first_name' => 'Joel',
                'last_name' => 'Ssekimpi',
                'email' => 'joel.ssekimpi@citygeneral.ug',
                'phone' => '+256 701 210005',
                'department' => 'Nursing',
                'positions' => ['Registered Nurse', 'Senior Nurse'],
                'type' => StaffType::NURSING,
                'license_number' => null,
                'specialty' => 'Treatment Room Nursing',
                'branch_code' => 'CGH-ENT',
                'address' => ['city' => 'Entebbe', 'district' => 'Wakiso', 'state' => 'Central'],
            ],
            [
                'employee_number' => 'CGH-LAB-001',
                'first_name' => 'Lillian',
                'last_name' => 'Nabukeera',
                'email' => 'lillian.nabukeera@citygeneral.ug',
                'phone' => '+256 701 210006',
                'department' => 'Laboratory',
                'positions' => ['Laboratory Technician'],
                'type' => StaffType::ALLIED_HEALTH,
                'license_number' => null,
                'specialty' => 'Clinical Laboratory',
                'branch_code' => 'CGH-MAIN',
                'address' => ['city' => 'Kampala', 'district' => 'Central', 'state' => 'Central'],
            ],
        ];

        foreach ($staffBlueprints as $staffData) {
            $department = $this->findDepartment($tenant, $staffData['department']);
            $position = $this->findPosition($tenant, $staffData['positions']);
            $branch = $branches->get($staffData['branch_code']);
            if (! $department instanceof Department) {
                continue;
            }

            if (! $position instanceof StaffPosition) {
                continue;
            }

            if (! $branch instanceof FacilityBranch) {
                continue;
            }

            $address = $this->upsertAddress($staffData['address'], $this->ugandaCountry());

            $staff = Staff::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'email' => $staffData['email'],
                ],
                [
                    'employee_number' => $staffData['employee_number'],
                    'first_name' => $staffData['first_name'],
                    'last_name' => $staffData['last_name'],
                    'phone' => $staffData['phone'],
                    'address_id' => $address->id,
                    'staff_position_id' => $position->id,
                    'type' => $staffData['type']->value,
                    'license_number' => $staffData['license_number'],
                    'specialty' => $staffData['specialty'],
                    'hire_date' => now()->subMonths(8)->toDateString(),
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );

            $staff->branches()->syncWithoutDetaching([
                $branch->id => ['is_primary_location' => true],
            ]);

            $staff->departments()->syncWithoutDetaching([$department->id]);
        }
    }

    /**
     * @param  Collection<string, FacilityBranch>  $branches
     */
    private function seedClinics(string $tenantId, ?string $staffId, Collection $branches): void
    {
        $tenant = $this->cityGeneralTenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        $clinics = [
            [
                'branch_code' => 'CGH-MAIN',
                'department' => 'Internal Medicine',
                'clinic_code' => 'CGH-OPD-MAIN',
                'clinic_name' => 'City General OPD',
                'location' => 'Main building, ground floor',
                'phone' => '+256 414 123460',
            ],
            [
                'branch_code' => 'CGH-MAIN',
                'department' => 'Pediatrics',
                'clinic_code' => 'CGH-PEDS-MAIN',
                'clinic_name' => 'Children Wellness Clinic',
                'location' => 'Main building, first floor',
                'phone' => '+256 414 123461',
            ],
            [
                'branch_code' => 'CGH-MAIN',
                'department' => 'Nursing',
                'clinic_code' => 'CGH-TREAT-MAIN',
                'clinic_name' => 'Main Treatment Room',
                'location' => 'Main building, urgent care wing',
                'phone' => '+256 414 123462',
            ],
            [
                'branch_code' => 'CGH-ENT',
                'department' => 'Surgery',
                'clinic_code' => 'CGH-OPD-ENT',
                'clinic_name' => 'Entebbe Surgical OPD',
                'location' => 'Entebbe branch, consultation bay A',
                'phone' => '+256 414 234570',
            ],
            [
                'branch_code' => 'CGH-ENT',
                'department' => 'Nursing',
                'clinic_code' => 'CGH-TREAT-ENT',
                'clinic_name' => 'Entebbe Dressing Room',
                'location' => 'Entebbe branch, treatment room',
                'phone' => '+256 414 234571',
            ],
        ];

        foreach ($clinics as $clinicData) {
            $department = $this->findDepartment($tenant, $clinicData['department']);
            $branch = $branches->get($clinicData['branch_code']);
            if (! $department instanceof Department) {
                continue;
            }

            if (! $branch instanceof FacilityBranch) {
                continue;
            }

            Clinic::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'clinic_code' => $clinicData['clinic_code'],
                ],
                [
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'clinic_name' => $clinicData['clinic_name'],
                    'location' => $clinicData['location'],
                    'phone' => $clinicData['phone'],
                    'status' => GeneralStatus::ACTIVE->value,
                    'created_by' => $staffId,
                    'updated_by' => $staffId,
                ],
            );
        }
    }

    private function seedFacilityServices(string $tenantId, ?string $userId): void
    {
        $services = [
            [
                'service_code' => 'CGH-SVC-NEB',
                'name' => 'Nebulization Session',
                'category' => FacilityServiceCategory::NURSING,
                'description' => 'Short-stay nebulization with nursing monitoring.',
                'cost_price' => 12000,
                'selling_price' => 25000,
                'is_billable' => true,
            ],
            [
                'service_code' => 'CGH-SVC-DRESS',
                'name' => 'Wound Dressing',
                'category' => FacilityServiceCategory::DRESSING,
                'description' => 'Sterile wound cleaning and dressing change.',
                'cost_price' => 15000,
                'selling_price' => 35000,
                'is_billable' => true,
            ],
            [
                'service_code' => 'CGH-SVC-IV',
                'name' => 'IV Cannulation',
                'category' => FacilityServiceCategory::NURSING,
                'description' => 'Peripheral IV line placement and setup.',
                'cost_price' => 8000,
                'selling_price' => 18000,
                'is_billable' => true,
            ],
            [
                'service_code' => 'CGH-SVC-PROC',
                'name' => 'Minor Procedure Pack',
                'category' => FacilityServiceCategory::PROCEDURE,
                'description' => 'Consumables and setup for a simple outpatient procedure.',
                'cost_price' => 30000,
                'selling_price' => 60000,
                'is_billable' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            FacilityService::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'service_code' => $serviceData['service_code'],
                ],
                [
                    'name' => $serviceData['name'],
                    'category' => $serviceData['category']->value,
                    'description' => $serviceData['description'],
                    'cost_price' => $serviceData['cost_price'],
                    'selling_price' => $serviceData['selling_price'],
                    'is_billable' => $serviceData['is_billable'],
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );
        }
    }

    private function seedLabCatalog(string $tenantId): void
    {
        $categories = LabTestCategory::query()
            ->whereNull('tenant_id')
            ->whereIn('name', ['Hematology', 'Chemistry', 'Parasitology'])
            ->get()
            ->keyBy('name');

        $resultTypes = LabResultType::query()
            ->whereNull('tenant_id')
            ->whereIn('code', ['parameter_panel', 'defined_option', 'free_entry'])
            ->get()
            ->keyBy('code');

        $specimenTypes = SpecimenType::query()
            ->whereNull('tenant_id')
            ->whereIn('name', ['Blood', 'Urine', 'Serum'])
            ->get()
            ->keyBy('name');

        $tests = [
            [
                'test_code' => 'CGH-LAB-CBC',
                'test_name' => 'Complete Blood Count',
                'category' => 'Hematology',
                'result_type' => 'parameter_panel',
                'description' => 'Basic full blood count panel for common outpatient workups.',
                'base_price' => 45000,
                'specimens' => ['Blood'],
                'parameters' => [
                    ['label' => 'Hemoglobin', 'unit' => 'g/dL', 'reference_range' => '12.0 - 16.0', 'value_type' => 'numeric'],
                    ['label' => 'WBC', 'unit' => 'x10^9/L', 'reference_range' => '4.0 - 11.0', 'value_type' => 'numeric'],
                    ['label' => 'Platelets', 'unit' => 'x10^9/L', 'reference_range' => '150 - 450', 'value_type' => 'numeric'],
                ],
                'options' => [],
            ],
            [
                'test_code' => 'CGH-LAB-MAL',
                'test_name' => 'Malaria Rapid Test',
                'category' => 'Parasitology',
                'result_type' => 'defined_option',
                'description' => 'Rapid antigen test for uncomplicated malaria screening.',
                'base_price' => 15000,
                'specimens' => ['Blood'],
                'parameters' => [],
                'options' => ['Positive', 'Negative'],
            ],
            [
                'test_code' => 'CGH-LAB-UA',
                'test_name' => 'Urinalysis',
                'category' => 'Chemistry',
                'result_type' => 'parameter_panel',
                'description' => 'Routine urine dipstick and microscopy screening.',
                'base_price' => 20000,
                'specimens' => ['Urine'],
                'parameters' => [
                    ['label' => 'Protein', 'unit' => null, 'reference_range' => 'Negative', 'value_type' => 'text'],
                    ['label' => 'Glucose', 'unit' => null, 'reference_range' => 'Negative', 'value_type' => 'text'],
                    ['label' => 'Leukocytes', 'unit' => null, 'reference_range' => '0 - 5 /hpf', 'value_type' => 'text'],
                ],
                'options' => [],
            ],
            [
                'test_code' => 'CGH-LAB-CRP',
                'test_name' => 'C-Reactive Protein',
                'category' => 'Chemistry',
                'result_type' => 'free_entry',
                'description' => 'Single-value inflammatory marker for acute infection assessment.',
                'base_price' => 30000,
                'specimens' => ['Serum'],
                'parameters' => [],
                'options' => [],
            ],
        ];

        foreach ($tests as $testData) {
            $category = $categories->get($testData['category']);
            $resultType = $resultTypes->get($testData['result_type']);
            if ($category === null) {
                continue;
            }

            if ($resultType === null) {
                continue;
            }

            $catalog = LabTestCatalog::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'test_code' => $testData['test_code'],
                ],
                [
                    'test_name' => $testData['test_name'],
                    'lab_test_category_id' => $category->id,
                    'result_type_id' => $resultType->id,
                    'description' => $testData['description'],
                    'base_price' => $testData['base_price'],
                    'is_active' => true,
                ],
            );

            $catalog->specimenTypes()->sync(
                collect($testData['specimens'])
                    ->map(fn (string $specimenName): ?string => $specimenTypes->get($specimenName)?->id)
                    ->filter()
                    ->values()
                    ->all(),
            );

            foreach ($testData['options'] as $index => $label) {
                $catalog->resultOptions()->updateOrCreate(
                    [
                        'lab_test_catalog_id' => $catalog->id,
                        'label' => $label,
                    ],
                    [
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ],
                );
            }

            foreach ($testData['parameters'] as $index => $parameter) {
                $catalog->resultParameters()->updateOrCreate(
                    [
                        'lab_test_catalog_id' => $catalog->id,
                        'label' => $parameter['label'],
                    ],
                    [
                        'unit' => $parameter['unit'],
                        'reference_range' => $parameter['reference_range'],
                        'value_type' => $parameter['value_type'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
