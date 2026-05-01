<?php

declare(strict_types=1);

use App\Actions\CreateAppointmentCategory;
use App\Actions\CreateAppointmentMode;
use App\Actions\CreateClinic;
use App\Actions\CreateCurrency;
use App\Actions\CreateDepartment;
use App\Actions\CreateFacilityService;
use App\Actions\CreateInsuranceCompany;
use App\Actions\CreateInsurancePackage;
use App\Actions\CreateLabTestCatalog;
use App\Actions\CreateReferralFacility;
use App\Actions\CreateStaffPosition;
use App\Actions\CreateUnit;
use App\Actions\DeleteAppointmentCategory;
use App\Actions\DeleteAppointmentMode;
use App\Actions\DeleteClinic;
use App\Actions\DeleteCurrency;
use App\Actions\DeleteDepartment;
use App\Actions\DeleteFacilityService;
use App\Actions\DeleteInsuranceCompany;
use App\Actions\DeleteInsurancePackage;
use App\Actions\DeleteLabTestCatalog;
use App\Actions\DeleteReferralFacility;
use App\Actions\DeleteStaffPosition;
use App\Actions\DeleteUnit;
use App\Actions\UpdateAppointmentCategory;
use App\Actions\UpdateAppointmentMode;
use App\Actions\UpdateClinic;
use App\Actions\UpdateCurrency;
use App\Actions\UpdateDepartment;
use App\Actions\UpdateFacilityService;
use App\Actions\UpdateInsuranceCompany;
use App\Actions\UpdateInsurancePackage;
use App\Actions\UpdateLabTestCatalog;
use App\Actions\UpdateReferralFacility;
use App\Actions\UpdateStaffPosition;
use App\Actions\UpdateUnit;
use App\Data\Clinical\CreateLabTestCatalogDTO;
use App\Data\Clinical\UpdateLabTestCatalogDTO;
use App\Data\Patient\CreateInsurancePackageDTO;
use App\Data\Patient\UpdateInsurancePackageDTO;
use App\Enums\FacilityServiceCategory;
use App\Enums\GeneralStatus;
use App\Enums\UnitType;
use App\Models\Activity;
use App\Models\FacilityBranch;
use App\Models\LabResultType;
use App\Models\LabTestCategory;
use App\Models\SpecimenType;
use App\Models\Tenant;
use App\Models\User;

it('records administration audit events for core master data actions', function (): void {
    $tenant = Tenant::factory()->create();
    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    $department = resolve(CreateDepartment::class)->handle([
        'tenant_id' => $tenant->id,
        'department_code' => 'ADM-DEP-001',
        'department_name' => 'Operations',
        'is_clinical' => false,
        'is_active' => true,
    ]);

    resolve(UpdateDepartment::class)->handle($department, [
        'department_name' => 'Operations and Quality',
        'is_active' => false,
    ]);

    $clinic = resolve(CreateClinic::class)->handle([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'clinic_code' => 'ADM-CL-001',
        'clinic_name' => 'Admin Clinic',
        'status' => GeneralStatus::ACTIVE->value,
    ]);

    resolve(UpdateClinic::class)->handle($clinic, [
        'clinic_name' => 'Admin Clinic Updated',
        'status' => GeneralStatus::INACTIVE->value,
    ]);

    $service = resolve(CreateFacilityService::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'Wound Review',
        'category' => FacilityServiceCategory::OTHER->value,
        'selling_price' => 0,
        'is_billable' => false,
        'is_active' => true,
    ]);

    resolve(UpdateFacilityService::class)->handle($service, [
        'name' => 'Wound Review Updated',
        'is_active' => false,
    ]);

    $category = resolve(CreateAppointmentCategory::class)->handle([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'clinic_id' => $clinic->id,
        'name' => 'Follow Up',
        'description' => 'Scheduled follow-up visits',
        'is_active' => true,
    ]);

    resolve(UpdateAppointmentCategory::class)->handle($category, [
        'name' => 'Follow Up Updated',
        'is_active' => false,
    ]);

    $mode = resolve(CreateAppointmentMode::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'Teleconsultation',
        'description' => 'Virtual review',
        'is_virtual' => true,
        'is_active' => true,
    ]);

    resolve(UpdateAppointmentMode::class)->handle($mode, [
        'name' => 'Teleconsultation Updated',
        'is_active' => false,
    ]);

    $referralFacility = resolve(CreateReferralFacility::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'City Heart Center',
        'facility_type' => 'Specialist Hospital',
        'contact_person' => 'Jane Admin',
        'phone' => '0700000001',
        'email' => 'referrals@example.test',
        'is_active' => true,
    ]);

    resolve(UpdateReferralFacility::class)->handle($referralFacility, [
        'name' => 'City Heart Center Updated',
        'is_active' => false,
    ]);

    $insuranceCompany = resolve(CreateInsuranceCompany::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'Acme Health',
        'email' => 'claims@acme.test',
        'main_contact' => '0700000002',
        'status' => GeneralStatus::ACTIVE->value,
    ]);

    resolve(UpdateInsuranceCompany::class)->handle($insuranceCompany, [
        'name' => 'Acme Health Updated',
        'status' => GeneralStatus::INACTIVE->value,
    ]);

    $insurancePackage = resolve(CreateInsurancePackage::class)->handle(
        new CreateInsurancePackageDTO(
            insuranceCompanyId: $insuranceCompany->id,
            name: 'Corporate Gold',
            status: GeneralStatus::ACTIVE->value,
        ),
    );

    resolve(UpdateInsurancePackage::class)->handle(
        $insurancePackage,
        new UpdateInsurancePackageDTO(
            insuranceCompanyId: $insuranceCompany->id,
            name: 'Corporate Gold Updated',
            status: GeneralStatus::INACTIVE->value,
        ),
    );

    $staffPosition = resolve(CreateStaffPosition::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'Senior Nurse',
        'description' => 'Clinical ward lead',
        'is_active' => true,
    ]);

    resolve(UpdateStaffPosition::class)->handle($staffPosition, [
        'name' => 'Senior Nurse Updated',
        'is_active' => false,
    ]);

    $unit = resolve(CreateUnit::class)->handle([
        'tenant_id' => $tenant->id,
        'name' => 'Milligram',
        'symbol' => 'mg',
        'description' => 'Mass unit',
        'type' => UnitType::MASS->value,
    ]);

    resolve(UpdateUnit::class)->handle($unit, [
        'name' => 'Milligram Updated',
        'description' => 'Mass unit updated',
        'type' => UnitType::MASS->value,
    ]);

    $labTestCategory = LabTestCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Point of Care',
        'description' => 'Rapid bedside testing',
        'is_active' => true,
    ]);

    $labTestCategory->update([
        'name' => 'Point of Care Updated',
        'is_active' => false,
    ]);

    $specimenType = SpecimenType::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Saliva',
        'description' => 'Saliva sample',
        'is_active' => true,
    ]);

    $specimenType->update([
        'name' => 'Saliva Updated',
        'is_active' => false,
    ]);

    $resultType = LabResultType::query()->create([
        'tenant_id' => $tenant->id,
        'code' => 'tenant_numeric',
        'name' => 'Tenant Numeric',
        'description' => 'Tenant-owned numeric capture',
        'is_active' => true,
    ]);

    $resultType->update([
        'name' => 'Tenant Numeric Updated',
        'is_active' => false,
    ]);

    $labTestCatalog = resolve(CreateLabTestCatalog::class)->handle(
        new CreateLabTestCatalogDTO(
            testCode: 'AUD-LAB-001',
            testName: 'Audit Test Catalog',
            labTestCategoryId: $labTestCategory->id,
            resultTypeId: $resultType->id,
            description: 'Audit coverage for lab setup',
            basePrice: 15000,
            isActive: true,
            specimenTypeIds: [$specimenType->id],
            resultOptions: [],
            resultParameters: [],
        ),
    );

    resolve(UpdateLabTestCatalog::class)->handle(
        $labTestCatalog,
        new UpdateLabTestCatalogDTO(
            testCode: 'AUD-LAB-001',
            testName: 'Audit Test Catalog Updated',
            labTestCategoryId: $labTestCategory->id,
            resultTypeId: $resultType->id,
            description: 'Updated audit coverage for lab setup',
            basePrice: 18000,
            isActive: false,
            specimenTypeIds: [$specimenType->id],
            resultOptions: [],
            resultParameters: [],
        ),
    );

    $currency = resolve(CreateCurrency::class)->handle([
        'code' => 'AUD',
        'name' => 'Audit Currency',
        'symbol' => 'A$',
        'decimal_places' => 2,
        'symbol_position' => 'before',
        'modifiable' => true,
    ]);

    resolve(UpdateCurrency::class)->handle($currency, [
        'code' => 'AUD',
        'name' => 'Audit Currency Updated',
        'symbol' => 'AU$',
        'decimal_places' => 2,
        'symbol_position' => 'before',
        'modifiable' => true,
    ]);

    resolve(DeleteAppointmentCategory::class)->handle($category->fresh());
    resolve(DeleteAppointmentMode::class)->handle($mode->fresh());
    resolve(DeleteReferralFacility::class)->handle($referralFacility->fresh());
    resolve(DeleteClinic::class)->handle($clinic->fresh());
    resolve(DeleteDepartment::class)->handle($department->fresh());
    resolve(DeleteLabTestCatalog::class)->handle($labTestCatalog->fresh());
    resolve(DeleteInsurancePackage::class)->handle($insurancePackage->fresh());
    resolve(DeleteInsuranceCompany::class)->handle($insuranceCompany->fresh());
    resolve(DeleteStaffPosition::class)->handle($staffPosition->fresh());
    resolve(DeleteUnit::class)->handle($unit->fresh());
    resolve(DeleteCurrency::class)->handle($currency->fresh());
    $resultType->fresh()->delete();
    $labTestCategory->fresh()->delete();
    $specimenType->fresh()->delete();
    resolve(DeleteFacilityService::class)->handle($service->fresh());

    $events = Activity::query()
        ->where('log_name', 'administration')
        ->pluck('event')
        ->all();

    expect($events)->toContain(
        'department.created',
        'department.updated',
        'department.deleted',
        'clinic.created',
        'clinic.updated',
        'clinic.deleted',
        'facility_service.created',
        'facility_service.updated',
        'facility_service.deleted',
        'appointment_category.created',
        'appointment_category.updated',
        'appointment_category.deleted',
        'appointment_mode.created',
        'appointment_mode.updated',
        'appointment_mode.deleted',
        'referral_facility.created',
        'referral_facility.updated',
        'referral_facility.deleted',
        'insurance_company.created',
        'insurance_company.updated',
        'insurance_company.deleted',
        'insurance_package.created',
        'insurance_package.updated',
        'insurance_package.deleted',
        'staff_position.created',
        'staff_position.updated',
        'staff_position.deleted',
        'unit.created',
        'unit.updated',
        'unit.deleted',
        'lab_test_category.created',
        'lab_test_category.updated',
        'lab_test_category.deleted',
        'specimen_type.created',
        'specimen_type.updated',
        'specimen_type.deleted',
        'lab_result_type.created',
        'lab_result_type.updated',
        'lab_result_type.deleted',
        'lab_test_catalog.created',
        'lab_test_catalog.updated',
        'lab_test_catalog.deleted',
        'currency.created',
        'currency.updated',
        'currency.deleted',
    );

    expect(Activity::query()
        ->where('log_name', 'administration')
        ->where('event', 'clinic.created')
        ->where('tenant_id', $tenant->id)
        ->where('branch_id', $branch->id)
        ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'appointment_mode.created')
            ->where('tenant_id', $tenant->id)
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'insurance_company.created')
            ->where('tenant_id', $tenant->id)
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'referral_facility.created')
            ->where('tenant_id', $tenant->id)
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'lab_test_category.created')
            ->where('tenant_id', $tenant->id)
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'specimen_type.created')
            ->where('tenant_id', $tenant->id)
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'lab_test_catalog.created')
            ->where('tenant_id', $tenant->id)
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('log_name', 'administration')
            ->where('event', 'currency.created')
            ->exists())->toBeTrue();
});
