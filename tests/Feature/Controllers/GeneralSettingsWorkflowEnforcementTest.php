<?php

declare(strict_types=1);

use App\Enums\AttendanceType;
use App\Enums\BillingStatus;
use App\Enums\ConsciousLevel;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\LabRequestStatus;
use App\Enums\MobilityStatus;
use App\Enums\Priority;
use App\Enums\StaffType;
use App\Enums\TriageGrade;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Clinic;
use App\Models\Consultation;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\InventoryItem;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\SpecimenType;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantGeneralSetting;
use App\Models\TriageRecord;
use App\Models\User;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function nextGeneralSettingsWorkflowSequence(): int
{
    static $sequence = 1;

    return $sequence++;
}

function createGeneralSettingsWorkflowTenant(): array
{
    $sequence = nextGeneralSettingsWorkflowSequence();

    $country = Country::query()->create([
        'country_name' => 'Settings Workflow Country '.$sequence,
        'country_code' => 'SW'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Settings Workflow Package '.$sequence,
        'users' => 50,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Settings Workflow Tenant '.$sequence,
        'domain' => 'settings-workflow-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'SWC'.$sequence,
        'name' => 'Settings Workflow Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Settings Workflow Branch '.$sequence,
        'branch_code' => 'SWB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $department = Department::query()->create([
        'tenant_id' => $tenant->id,
        'department_code' => 'SWD'.$sequence,
        'department_name' => 'Outpatient '.$sequence,
        'is_clinical' => true,
        'is_active' => true,
    ]);

    $clinic = Clinic::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'clinic_code' => 'SWCL'.$sequence,
        'clinic_name' => 'General Clinic '.$sequence,
        'department_id' => $department->id,
        'status' => GeneralStatus::ACTIVE->value,
    ]);

    return [$tenant, $branch, $department, $clinic];
}

function createGeneralSettingsWorkflowUser(
    Tenant $tenant,
    FacilityBranch $branch,
    StaffType $staffType = StaffType::MEDICAL,
): User {
    $sequence = nextGeneralSettingsWorkflowSequence();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'SW-EMP-'.$sequence,
        'first_name' => 'Workflow',
        'last_name' => 'User'.$sequence,
        'email' => 'settings.workflow'.$sequence.'@test.com',
        'type' => $staffType,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'settings.workflow.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

function createGeneralSettingsWorkflowPatient(Tenant $tenant, User $user): Patient
{
    $sequence = nextGeneralSettingsWorkflowSequence();

    return Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'SW-PAT-'.$sequence,
        'first_name' => 'Payment',
        'last_name' => 'Patient'.$sequence,
        'gender' => 'female',
        'phone_number' => '+256700001'.$sequence,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function createGeneralSettingsWorkflowVisit(
    Tenant $tenant,
    FacilityBranch $branch,
    Clinic $clinic,
    Patient $patient,
    User $doctorUser,
    string $billingType = 'cash',
    BillingStatus $billingStatus = BillingStatus::PENDING,
    float $grossAmount = 50000,
    float $balanceAmount = 50000,
): PatientVisit {
    $sequence = nextGeneralSettingsWorkflowSequence();

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'SW-VIS-'.$sequence,
        'visit_type' => VisitType::OPD_CONSULTATION->value,
        'status' => VisitStatus::IN_PROGRESS->value,
        'clinic_id' => $clinic->id,
        'doctor_id' => $doctorUser->staff_id,
        'registered_at' => now(),
        'registered_by' => $doctorUser->id,
        'created_by' => $doctorUser->id,
        'updated_by' => $doctorUser->id,
    ]);

    $payer = VisitPayer::query()->create([
        'tenant_id' => $tenant->id,
        'patient_visit_id' => $visit->id,
        'billing_type' => $billingType,
        'created_by' => $doctorUser->id,
        'updated_by' => $doctorUser->id,
    ]);

    VisitBilling::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'patient_visit_id' => $visit->id,
        'visit_payer_id' => $payer->id,
        'payer_type' => $payer->billing_type,
        'status' => $billingStatus,
        'gross_amount' => $grossAmount,
        'paid_amount' => max(0, $grossAmount - $balanceAmount),
        'balance_amount' => $balanceAmount,
        'billed_at' => now(),
    ]);

    return $visit;
}

function createGeneralSettingsWorkflowTriage(
    PatientVisit $visit,
    Staff $nurse,
    Clinic $clinic,
): TriageRecord {
    return TriageRecord::query()->create([
        'tenant_id' => $visit->tenant_id,
        'facility_branch_id' => $visit->facility_branch_id,
        'visit_id' => $visit->id,
        'nurse_id' => $nurse->id,
        'triage_datetime' => now(),
        'triage_grade' => TriageGrade::GREEN->value,
        'attendance_type' => AttendanceType::NEW->value,
        'conscious_level' => ConsciousLevel::ALERT->value,
        'mobility_status' => MobilityStatus::INDEPENDENT->value,
        'chief_complaint' => 'Headache',
        'assigned_clinic_id' => $clinic->id,
    ]);
}

function createGeneralSettingsWorkflowConsultation(
    PatientVisit $visit,
    Staff $doctor,
): Consultation {
    return Consultation::query()->create([
        'tenant_id' => $visit->tenant_id,
        'facility_branch_id' => $visit->facility_branch_id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'started_at' => now(),
        'chief_complaint' => 'Headache',
        'assessment' => 'Clinical review started',
    ]);
}

function createGeneralSettingsWorkflowLabTest(Tenant $tenant): LabTestCatalog
{
    $sequence = nextGeneralSettingsWorkflowSequence();
    $category = LabTestCategory::query()->where('name', 'Hematology')->firstOrFail();
    $specimenType = SpecimenType::query()->where('name', 'Blood')->firstOrFail();
    $resultType = LabResultType::query()->where('code', 'free_entry')->firstOrFail();

    $labTest = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => 'SW-LAB-'.$sequence,
        'test_name' => 'Workflow Lab Test '.$sequence,
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 0,
        'is_active' => true,
    ]);

    $labTest->specimenTypes()->sync([$specimenType->id]);

    return $labTest;
}

function createGeneralSettingsWorkflowDrug(Tenant $tenant, User $user): InventoryItem
{
    $sequence = nextGeneralSettingsWorkflowSequence();

    return InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'item_type' => InventoryItemType::DRUG,
        'name' => 'Workflow Drug '.$sequence,
        'generic_name' => 'Workflow Drug '.$sequence,
        'category' => 'other',
        'dosage_form' => 'tablet',
        'strength' => '500mg',
        'expires' => true,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function createGeneralSettingsWorkflowFacilityService(Tenant $tenant, User $user): FacilityService
{
    $sequence = nextGeneralSettingsWorkflowSequence();

    return FacilityService::query()->create([
        'tenant_id' => $tenant->id,
        'service_code' => 'SW-SVC-'.$sequence,
        'name' => 'Workflow Service '.$sequence,
        'category' => 'other',
        'is_billable' => false,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function storeGeneralSetting(Tenant $tenant, string $key, string $value): void
{
    TenantGeneralSetting::query()->updateOrCreate(
        ['tenant_id' => $tenant->id, 'key' => $key],
        ['value' => $value],
    );
}

it('allows consultation start even when payment-before-consultation is enabled and the visit is unpaid', function (): void {
    [$tenant, $branch, , $clinic] = createGeneralSettingsWorkflowTenant();
    $doctorUser = createGeneralSettingsWorkflowUser($tenant, $branch);
    $nurseUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::NURSING);
    $patient = createGeneralSettingsWorkflowPatient($tenant, $doctorUser);
    $visit = createGeneralSettingsWorkflowVisit($tenant, $branch, $clinic, $patient, $doctorUser);

    $doctorUser->givePermissionTo('consultations.create');
    createGeneralSettingsWorkflowTriage($visit, $nurseUser->staff, $clinic);
    storeGeneralSetting($tenant, 'payments.require_payment_before_consultation', '1');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($doctorUser)
        ->post(route('doctors.consultations.store', $visit), [
            'chief_complaint' => 'Headache',
        ]);

    $response->assertRedirectToRoute('doctors.consultations.show', $visit);
    $response->assertSessionHas('success', 'Consultation started successfully.');

    expect($visit->consultation()->exists())->toBeTrue();
});

it('allows consultation lab orders even when payment-before-laboratory is enabled and the visit is unpaid', function (): void {
    [$tenant, $branch, , $clinic] = createGeneralSettingsWorkflowTenant();
    $doctorUser = createGeneralSettingsWorkflowUser($tenant, $branch);
    $nurseUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::NURSING);
    $patient = createGeneralSettingsWorkflowPatient($tenant, $doctorUser);
    $visit = createGeneralSettingsWorkflowVisit($tenant, $branch, $clinic, $patient, $doctorUser);
    $labTest = createGeneralSettingsWorkflowLabTest($tenant);

    $doctorUser->givePermissionTo('consultations.update');
    createGeneralSettingsWorkflowTriage($visit, $nurseUser->staff, $clinic);
    createGeneralSettingsWorkflowConsultation($visit, $doctorUser->staff);
    storeGeneralSetting($tenant, 'payments.require_payment_before_laboratory', '1');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($doctorUser)
        ->post(route('doctors.consultations.lab-requests.store', $visit), [
            'test_ids' => [$labTest->id],
            'priority' => Priority::ROUTINE->value,
            'clinical_notes' => 'Check baseline labs',
        ]);

    $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'lab']));
    $response->assertSessionHas('success', 'Laboratory request created successfully.');
});

it('allows prescriptions even when payment-before-pharmacy is enabled and the visit is unpaid', function (): void {
    [$tenant, $branch, , $clinic] = createGeneralSettingsWorkflowTenant();
    $doctorUser = createGeneralSettingsWorkflowUser($tenant, $branch);
    $nurseUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::NURSING);
    $patient = createGeneralSettingsWorkflowPatient($tenant, $doctorUser);
    $visit = createGeneralSettingsWorkflowVisit($tenant, $branch, $clinic, $patient, $doctorUser);
    $drug = createGeneralSettingsWorkflowDrug($tenant, $doctorUser);

    $doctorUser->givePermissionTo('consultations.update');
    createGeneralSettingsWorkflowTriage($visit, $nurseUser->staff, $clinic);
    createGeneralSettingsWorkflowConsultation($visit, $doctorUser->staff);
    storeGeneralSetting($tenant, 'payments.require_payment_before_pharmacy', '1');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($doctorUser)
        ->post(route('doctors.consultations.prescriptions.store', $visit), [
            'primary_diagnosis' => 'Headache',
            'items' => [[
                'inventory_item_id' => $drug->id,
                'dosage' => '500mg',
                'frequency' => 'BD',
                'route' => 'oral',
                'duration_days' => 5,
                'quantity' => 10,
            ]],
        ]);

    $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'prescriptions']));
    $response->assertSessionHas('success', 'Prescription created successfully.');
});

it('allows facility service orders even when payment-before-procedures is enabled and the visit is unpaid', function (): void {
    [$tenant, $branch, , $clinic] = createGeneralSettingsWorkflowTenant();
    $doctorUser = createGeneralSettingsWorkflowUser($tenant, $branch);
    $nurseUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::NURSING);
    $patient = createGeneralSettingsWorkflowPatient($tenant, $doctorUser);
    $visit = createGeneralSettingsWorkflowVisit($tenant, $branch, $clinic, $patient, $doctorUser);
    $service = createGeneralSettingsWorkflowFacilityService($tenant, $doctorUser);

    $doctorUser->givePermissionTo('consultations.update');
    createGeneralSettingsWorkflowTriage($visit, $nurseUser->staff, $clinic);
    createGeneralSettingsWorkflowConsultation($visit, $doctorUser->staff);
    storeGeneralSetting($tenant, 'payments.require_payment_before_procedures', '1');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($doctorUser)
        ->post(route('doctors.consultations.facility-service-orders.store', $visit), [
            'facility_service_id' => $service->id,
        ]);

    $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services']));
    $response->assertSessionHas('success', 'Facility service order created successfully.');
});

it('shows payment block message on lab delivery page when payment-before-laboratory is enabled and visit is unpaid', function (): void {
    [$tenant, $branch, , $clinic] = createGeneralSettingsWorkflowTenant();
    $doctorUser = createGeneralSettingsWorkflowUser($tenant, $branch);
    $nurseUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::NURSING);
    $patient = createGeneralSettingsWorkflowPatient($tenant, $doctorUser);
    $visit = createGeneralSettingsWorkflowVisit($tenant, $branch, $clinic, $patient, $doctorUser);
    $labTest = createGeneralSettingsWorkflowLabTest($tenant);

    $labUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::TECHNICAL);
    $labUser->givePermissionTo('lab_requests.view');

    createGeneralSettingsWorkflowTriage($visit, $nurseUser->staff, $clinic);
    $consultation = createGeneralSettingsWorkflowConsultation($visit, $doctorUser->staff);
    storeGeneralSetting($tenant, 'payments.require_payment_before_laboratory', '1');

    $labRequest = LabRequest::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_id' => $visit->id,
        'consultation_id' => $consultation->id,
        'requested_by' => $doctorUser->staff_id,
        'request_date' => now(),
        'priority' => Priority::ROUTINE->value,
        'status' => LabRequestStatus::REQUESTED,
        'clinical_notes' => 'Check baseline labs',
    ]);

    $labRequestItem = LabRequestItem::query()->create([
        'request_id' => $labRequest->id,
        'test_id' => $labTest->id,
        'status' => 'pending',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($labUser)
        ->get(route('laboratory.request-items.show', $labRequestItem));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/request-item')
            ->where(
                'paymentBlockMessage',
                'Laboratory orders are blocked until the visit is paid or allowed insurance cover is in place.',
            ),
    );
});

it('does not show payment block message on lab delivery page when visit is paid', function (): void {
    [$tenant, $branch, , $clinic] = createGeneralSettingsWorkflowTenant();
    $doctorUser = createGeneralSettingsWorkflowUser($tenant, $branch);
    $nurseUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::NURSING);
    $patient = createGeneralSettingsWorkflowPatient($tenant, $doctorUser);
    $visit = createGeneralSettingsWorkflowVisit(
        $tenant,
        $branch,
        $clinic,
        $patient,
        $doctorUser,
        billingStatus: BillingStatus::FULLY_PAID,
        balanceAmount: 0,
    );
    $labTest = createGeneralSettingsWorkflowLabTest($tenant);

    $labUser = createGeneralSettingsWorkflowUser($tenant, $branch, StaffType::TECHNICAL);
    $labUser->givePermissionTo('lab_requests.view');

    createGeneralSettingsWorkflowTriage($visit, $nurseUser->staff, $clinic);
    $consultation = createGeneralSettingsWorkflowConsultation($visit, $doctorUser->staff);
    storeGeneralSetting($tenant, 'payments.require_payment_before_laboratory', '1');

    $labRequest = LabRequest::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_id' => $visit->id,
        'consultation_id' => $consultation->id,
        'requested_by' => $doctorUser->staff_id,
        'request_date' => now(),
        'priority' => Priority::ROUTINE->value,
        'status' => LabRequestStatus::REQUESTED,
        'clinical_notes' => 'Paid visit labs',
    ]);

    $labRequestItem = LabRequestItem::query()->create([
        'request_id' => $labRequest->id,
        'test_id' => $labTest->id,
        'status' => 'pending',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($labUser)
        ->get(route('laboratory.request-items.show', $labRequestItem));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/request-item')
            ->where('paymentBlockMessage', null),
    );
});
