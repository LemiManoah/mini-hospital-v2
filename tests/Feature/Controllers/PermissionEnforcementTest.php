<?php

declare(strict_types=1);

use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\MobilityStatus;
use App\Enums\Priority;
use App\Enums\StaffType;
use App\Enums\TriageGrade;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Consultation;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\FacilityServiceOrder;
use App\Models\InventoryItem;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\SpecimenType;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TriageRecord;
use App\Models\User;
use App\Models\VisitBilling;
use App\Models\VisitPayer;
use Database\Seeders\PermissionSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function nextPermissionTestSequence(): int
{
    static $sequence = 1;

    return $sequence++;
}

function createPermissionTenant(bool $withBranch = false): array
{
    $sequence = nextPermissionTestSequence();

    $country = Country::query()->create([
        'country_name' => 'Permission Country '.$sequence,
        'country_code' => 'PC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Permission Package '.$sequence,
        'users' => 100 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Permission Tenant '.$sequence,
        'domain' => 'permission-'.$sequence.'.test',
        'has_branches' => $withBranch,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::CLINIC,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    if (! $withBranch) {
        return [$tenant, null, null, null];
    }

    $currency = Currency::query()->create([
        'code' => 'PCU'.$sequence,
        'name' => 'Permission Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Main Branch '.$sequence,
        'branch_code' => 'PB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $department = Department::query()->create([
        'tenant_id' => $tenant->id,
        'department_code' => 'PD'.$sequence,
        'department_name' => 'Outpatient '.$sequence,
        'is_clinical' => true,
        'is_active' => true,
    ]);

    $clinic = Clinic::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'clinic_code' => 'CL'.$sequence,
        'clinic_name' => 'General Clinic '.$sequence,
        'department_id' => $department->id,
        'status' => GeneralStatus::ACTIVE->value,
    ]);

    return [$tenant, $branch, $department, $clinic];
}

function createPermissionUser(
    Tenant $tenant,
    bool $withStaff = false,
    bool $isSupport = false,
    ?FacilityBranch $branch = null,
    StaffType $staffType = StaffType::MEDICAL,
): User {
    $staff = null;

    if ($withStaff) {
        $sequence = nextPermissionTestSequence();

        $staff = Staff::query()->create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'EMP-'.$sequence,
            'first_name' => 'Staff',
            'last_name' => 'User'.$sequence,
            'email' => 'staff'.$sequence.'@permission.test',
            'type' => $staffType,
            'hire_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        if ($branch instanceof FacilityBranch) {
            $staff->branches()->sync([
                $branch->id => ['is_primary_location' => true],
            ]);
        }
    }

    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff?->id,
        'email_verified_at' => now(),
        'is_support' => $isSupport,
    ]);
}

function createPermissionPatient(Tenant $tenant, User $user): Patient
{
    $sequence = nextPermissionTestSequence();

    return Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-'.$sequence,
        'first_name' => 'Patient',
        'last_name' => 'Number'.$sequence,
        'gender' => 'female',
        'phone_number' => '+256700000'.$sequence,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function createPermissionVisit(
    Tenant $tenant,
    Patient $patient,
    User $user,
    ?FacilityBranch $branch,
    ?Clinic $clinic,
    ?Staff $doctor = null,
    VisitStatus $status = VisitStatus::REGISTERED,
): PatientVisit {
    $sequence = nextPermissionTestSequence();

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch?->id,
        'visit_number' => 'VIS-'.$sequence,
        'visit_type' => VisitType::OPD_CONSULTATION->value,
        'status' => $status->value,
        'clinic_id' => $clinic?->id,
        'doctor_id' => $doctor?->id,
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $payer = VisitPayer::query()->create([
        'tenant_id' => $tenant->id,
        'patient_visit_id' => $visit->id,
        'billing_type' => 'cash',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    VisitBilling::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch?->id,
        'patient_visit_id' => $visit->id,
        'visit_payer_id' => $payer->id,
        'payer_type' => $payer->billing_type,
    ]);

    return $visit;
}

function createPermissionTriage(
    PatientVisit $visit,
    Staff $nurse,
    ?Clinic $clinic = null,
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
        'assigned_clinic_id' => $clinic?->id,
    ]);
}

function createPermissionConsultation(PatientVisit $visit, Staff $doctor): Consultation
{
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

function createPermissionAppointment(
    Tenant $tenant,
    Patient $patient,
    User $user,
): Appointment {
    return Appointment::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'appointment_date' => now()->toDateString(),
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
        'reason_for_visit' => 'Review visit',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function createPermissionLabTest(Tenant $tenant, Department $department): LabTestCatalog
{
    $sequence = nextPermissionTestSequence();
    $category = LabTestCategory::query()->where('name', 'Hematology')->firstOrFail();
    $specimenType = SpecimenType::query()->where('name', 'Blood')->firstOrFail();
    $resultType = LabResultType::query()->where('code', 'free_entry')->firstOrFail();

    $labTest = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => 'LAB-'.$sequence,
        'test_name' => 'Full Blood Count '.$sequence,
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 0,
        'is_active' => true,
    ]);

    $labTest->specimenTypes()->sync([$specimenType->id]);

    return $labTest->refresh();
}

function createPermissionDrug(Tenant $tenant, User $user): InventoryItem
{
    $sequence = nextPermissionTestSequence();

    return InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'item_type' => InventoryItemType::DRUG,
        'name' => 'Paracetamol '.$sequence,
        'generic_name' => 'Paracetamol '.$sequence,
        'category' => 'other',
        'dosage_form' => 'tablet',
        'strength' => '500mg',
        'expires' => true,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function createPermissionFacilityService(Tenant $tenant, User $user): FacilityService
{
    $sequence = nextPermissionTestSequence();

    return FacilityService::query()->create([
        'tenant_id' => $tenant->id,
        'service_code' => 'SVC-'.$sequence,
        'name' => 'ECG '.$sequence,
        'category' => 'other',
        'is_billable' => false,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

describe('Core permission pages', function (): void {
    it('forbids and allows users index based on users.view permission', function (): void {
        [$tenant] = createPermissionTenant();

        $user = createPermissionUser($tenant);
        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();

        $user->givePermissionTo('users.view');

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk();
    });

    it('forbids and allows dashboard based on dashboard.view permission', function (): void {
        [$tenant] = createPermissionTenant();

        $user = createPermissionUser($tenant);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $user->givePermissionTo('dashboard.view');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    });

    it('forbids and allows facility branch index based on facility_branches.view permission', function (): void {
        [$tenant] = createPermissionTenant(withBranch: true);

        $user = createPermissionUser($tenant);
        $this->actingAs($user)
            ->get(route('facility-branches.index'))
            ->assertForbidden();

        $user->givePermissionTo('facility_branches.view');

        $this->actingAs($user)
            ->get(route('facility-branches.index'))
            ->assertOk();
    });

    it('forbids and allows facility branch creation page based on facility_branches.create permission', function (): void {
        [$tenant] = createPermissionTenant(withBranch: true);

        $user = createPermissionUser($tenant);
        $this->actingAs($user)
            ->get(route('facility-branches.create'))
            ->assertForbidden();

        $user->givePermissionTo('facility_branches.create');

        $this->actingAs($user)
            ->get(route('facility-branches.create'))
            ->assertOk();
    });
});

describe('Tenant support and onboarding permissions', function (): void {
    it('forbids and allows support users opening the facility switcher based on tenants.view permission', function (): void {
        createPermissionTenant();
        [$supportTenant] = createPermissionTenant();

        $supportUser = createPermissionUser($supportTenant, isSupport: true);

        $this->actingAs($supportUser)
            ->get(route('facility-switcher.index'))
            ->assertForbidden();

        $supportUser->givePermissionTo('tenants.view');

        $this->actingAs($supportUser)
            ->get(route('facility-switcher.index'))
            ->assertOk();
    });

    it('forbids and allows support users switching tenant context based on tenants.update permission', function (): void {
        [$sourceTenant] = createPermissionTenant();
        [$targetTenant] = createPermissionTenant();

        $supportUser = createPermissionUser($sourceTenant, isSupport: true);

        $this->actingAs($supportUser)
            ->post(route('facility-switcher.switch', $targetTenant->id))
            ->assertForbidden();

        $supportUser->givePermissionTo('tenants.update');

        $response = $this->actingAs($supportUser)
            ->post(route('facility-switcher.switch', $targetTenant->id));

        $response->assertRedirectToRoute('branch-switcher.index');
        $response->assertSessionHas('success', 'Switched to '.$targetTenant->name);

        expect($supportUser->fresh()?->tenant_id)->toBe($targetTenant->id);
    });

    it('forbids and allows onboarding access based on tenants.onboard permission', function (): void {
        [$tenant] = createPermissionTenant();
        $tenant->update([
            'onboarding_completed_at' => null,
            'onboarding_current_step' => 'profile',
        ]);

        $user = createPermissionUser($tenant);

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertForbidden();

        $user->givePermissionTo('tenants.onboard');

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertOk();
    });
});

describe('Visit workflow permissions', function (): void {
    it('forbids and allows visit status updates based on visits.update permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $user = createPermissionUser($tenant);
        $patient = createPermissionPatient($tenant, $user);
        $visit = createPermissionVisit($tenant, $patient, $user, $branch, $clinic);

        $payload = [
            'status' => VisitStatus::IN_PROGRESS->value,
            'redirect_to' => 'show',
        ];

        $this->actingAs($user)
            ->patch(route('visits.update-status', $visit), $payload)
            ->assertForbidden();

        $user->givePermissionTo('visits.update');

        $response = $this->actingAs($user)
            ->patch(route('visits.update-status', $visit), $payload);

        $response->assertRedirectToRoute('visits.show', $visit);
        $response->assertSessionHas('success', 'Visit status updated successfully.');

        expect($visit->fresh()->status)->toBe(VisitStatus::IN_PROGRESS);
    });

    it('forbids and allows triage creation based on triage.create permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $nurseUser);
        $visit = createPermissionVisit($tenant, $patient, $nurseUser, $branch, $clinic);

        $payload = [
            'redirect_to' => 'triage',
            'triage_grade' => TriageGrade::GREEN->value,
            'attendance_type' => AttendanceType::NEW->value,
            'conscious_level' => ConsciousLevel::ALERT->value,
            'mobility_status' => MobilityStatus::INDEPENDENT->value,
            'chief_complaint' => 'Headache',
            'assigned_clinic_id' => $clinic?->id,
        ];

        $this->actingAs($nurseUser)
            ->post(route('visits.triage.store', $visit), $payload)
            ->assertForbidden();

        $nurseUser->givePermissionTo('triage.create');

        $response = $this->actingAs($nurseUser)
            ->post(route('visits.triage.store', $visit), $payload);

        $response->assertRedirectToRoute('triage.show', $visit);
        $response->assertSessionHas('success', 'Triage recorded successfully.');

        $this->assertDatabaseHas('triage_records', [
            'visit_id' => $visit->id,
            'nurse_id' => $nurseUser->staff_id,
        ]);
    });

    it('forbids and allows vital sign creation based on triage.update permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $nurseUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $nurseUser,
            $branch,
            $clinic,
            status: VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);

        $payload = [
            'redirect_to' => 'triage',
            'temperature' => 37.1,
            'temperature_unit' => 'celsius',
            'pulse_rate' => 82,
            'blood_glucose_unit' => 'mg_dl',
        ];

        $this->actingAs($nurseUser)
            ->post(route('visits.vitals.store', $visit), $payload)
            ->assertForbidden();

        $nurseUser->givePermissionTo('triage.update');

        $response = $this->actingAs($nurseUser)
            ->post(route('visits.vitals.store', $visit), $payload);

        $response->assertRedirectToRoute('triage.show', $visit);
        $response->assertSessionHas('success', 'Vital signs recorded successfully.');

        $this->assertDatabaseHas('vital_signs', [
            'triage_id' => $visit->triage->id,
            'recorded_by' => $nurseUser->staff_id,
        ]);
    });
});

describe('Consultation workflow permissions', function (): void {
    it('allows support users with consultation permission to open the consultation queue without a staff profile', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $supportUser = createPermissionUser($tenant, isSupport: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);

        $supportUser->givePermissionTo('consultations.view');

        $this->withSession(['active_branch_id' => $branch->id])
            ->actingAs($supportUser)
            ->get(route('doctors.consultations.index'))
            ->assertOk();
    });

    it('allows support users with consultation permission to open a consultation workspace without a staff profile', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $supportUser = createPermissionUser($tenant, isSupport: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);
        createPermissionConsultation($visit, $doctorUser->staff);

        $supportUser->givePermissionTo('consultations.view');

        $this->withSession(['active_branch_id' => $branch->id])
            ->actingAs($supportUser)
            ->get(route('doctors.consultations.show', $visit))
            ->assertOk();
    });

    it('forbids and allows consultation creation based on consultations.create permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);

        $payload = [
            'chief_complaint' => 'Headache',
            'assessment' => 'Observation needed',
        ];

        $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.store', $visit), $payload)
            ->assertForbidden();

        $doctorUser->givePermissionTo('consultations.create');

        $response = $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.store', $visit), $payload);

        $response->assertRedirectToRoute('doctors.consultations.show', $visit);
        $response->assertSessionHas('success', 'Consultation started successfully.');

        $this->assertDatabaseHas('consultations', [
            'visit_id' => $visit->id,
            'doctor_id' => $doctorUser->staff_id,
        ]);
    });

    it('forbids and allows consultation updates based on consultations.update permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);
        createPermissionConsultation($visit, $doctorUser->staff);

        $payload = [
            'intent' => 'save_draft',
            'chief_complaint' => 'Updated complaint',
            'assessment' => 'Updated assessment',
        ];

        $this->actingAs($doctorUser)
            ->put(route('doctors.consultations.update', $visit), $payload)
            ->assertForbidden();

        $doctorUser->givePermissionTo('consultations.update');

        $response = $this->actingAs($doctorUser)
            ->put(route('doctors.consultations.update', $visit), $payload);

        $response->assertRedirectToRoute('doctors.consultations.show', $visit);
        $response->assertSessionHas('success', 'Consultation saved successfully.');

        expect($visit->consultation->fresh()->assessment)->toBe('Updated assessment');
    });

    it('forbids and allows lab request creation based on consultations.update permission', function (): void {
        [$tenant, $branch, $department, $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);
        createPermissionConsultation($visit, $doctorUser->staff);
        $labTest = createPermissionLabTest($tenant, $department);

        $payload = [
            'test_ids' => [$labTest->id],
            'priority' => Priority::ROUTINE->value,
            'clinical_notes' => 'Check baseline labs',
        ];

        $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.lab-requests.store', $visit), $payload)
            ->assertForbidden();

        $doctorUser->givePermissionTo('consultations.update');

        $response = $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.lab-requests.store', $visit), $payload);

        $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'lab']));
        $response->assertSessionHas('success', 'Laboratory request created successfully.');
    });

    it('forbids and allows prescription creation based on consultations.update permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);
        createPermissionConsultation($visit, $doctorUser->staff);
        $drug = createPermissionDrug($tenant, $doctorUser);

        $payload = [
            'primary_diagnosis' => 'Headache',
            'items' => [[
                'inventory_item_id' => $drug->id,
                'dosage' => '500mg',
                'frequency' => 'BD',
                'route' => 'oral',
                'duration_days' => 5,
                'quantity' => 10,
            ]],
        ];

        $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.prescriptions.store', $visit), $payload)
            ->assertForbidden();

        $doctorUser->givePermissionTo('consultations.update');

        $response = $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.prescriptions.store', $visit), $payload);

        $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'prescriptions']));
        $response->assertSessionHas('success', 'Prescription created successfully.');
    });

    it('forbids and allows facility service orders based on consultations.update permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);
        createPermissionConsultation($visit, $doctorUser->staff);
        $service = createPermissionFacilityService($tenant, $doctorUser);

        $payload = [
            'facility_service_id' => $service->id,
        ];

        $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.facility-service-orders.store', $visit), $payload)
            ->assertForbidden();

        $doctorUser->givePermissionTo('consultations.update');

        $response = $this->actingAs($doctorUser)
            ->post(route('doctors.consultations.facility-service-orders.store', $visit), $payload);

        $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services']));
        $response->assertSessionHas('success', 'Facility service order created successfully.');
    });

    it('forbids and allows pending facility service order removal based on consultations.update permission', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $nurseUser = createPermissionUser($tenant, withStaff: true, branch: $branch, staffType: StaffType::NURSING);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit(
            $tenant,
            $patient,
            $doctorUser,
            $branch,
            $clinic,
            $doctorUser->staff,
            VisitStatus::IN_PROGRESS,
        );
        createPermissionTriage($visit, $nurseUser->staff, $clinic);
        $consultation = createPermissionConsultation($visit, $doctorUser->staff);
        $service = createPermissionFacilityService($tenant, $doctorUser);

        $order = FacilityServiceOrder::query()->create([
            'tenant_id' => $tenant->id,
            'facility_branch_id' => $branch?->id,
            'visit_id' => $visit->id,
            'consultation_id' => $consultation->id,
            'facility_service_id' => $service->id,
            'ordered_by' => $doctorUser->staff_id,
            'status' => 'pending',
            'ordered_at' => now(),
        ]);

        $this->actingAs($doctorUser)
            ->delete(route('doctors.consultations.facility-service-orders.destroy', [
                'visit' => $visit,
                'facilityServiceOrder' => $order,
            ]))
            ->assertForbidden();

        $doctorUser->givePermissionTo('consultations.update');

        $response = $this->actingAs($doctorUser)
            ->delete(route('doctors.consultations.facility-service-orders.destroy', [
                'visit' => $visit,
                'facilityServiceOrder' => $order,
            ]));

        $response->assertRedirect(route('doctors.consultations.show', ['visit' => $visit, 'tab' => 'services']));
        $response->assertSessionHas('success', 'Facility service order removed successfully.');
        $this->assertDatabaseMissing('facility_service_orders', ['id' => $order->id]);
    });

    it('blocks facility service deletion when service orders exist', function (): void {
        [$tenant, $branch, , $clinic] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $patient = createPermissionPatient($tenant, $doctorUser);
        $visit = createPermissionVisit($tenant, $patient, $doctorUser, $branch, $clinic);
        $service = createPermissionFacilityService($tenant, $doctorUser);

        FacilityServiceOrder::query()->create([
            'tenant_id' => $tenant->id,
            'facility_branch_id' => $branch?->id,
            'visit_id' => $visit->id,
            'facility_service_id' => $service->id,
            'ordered_by' => $doctorUser->staff_id,
            'status' => 'pending',
            'ordered_at' => now(),
        ]);

        $doctorUser->givePermissionTo('facility_services.delete');

        $response = $this->withSession(['active_branch_id' => $branch?->id])
            ->actingAs($doctorUser)
            ->delete(route('facility-services.destroy', $service));

        $response->assertRedirectToRoute('facility-services.index');
        $response->assertSessionHas('error', 'This facility service cannot be deleted because it has existing service orders.');
        $this->assertDatabaseHas('facility_services', ['id' => $service->id]);
    });

    it('allows facility service deletion when no service orders exist', function (): void {
        [$tenant, $branch] = createPermissionTenant(withBranch: true);
        $doctorUser = createPermissionUser($tenant, withStaff: true, branch: $branch);
        $service = createPermissionFacilityService($tenant, $doctorUser);

        $doctorUser->givePermissionTo('facility_services.delete');

        $response = $this->withSession(['active_branch_id' => $branch?->id])
            ->actingAs($doctorUser)
            ->delete(route('facility-services.destroy', $service));

        $response->assertRedirectToRoute('facility-services.index');
        $response->assertSessionHas('success', 'Facility service deleted successfully.');
        $this->assertDatabaseMissing('facility_services', ['id' => $service->id]);
    });
});

describe('Appointment action permissions', function (): void {
    it('forbids and allows appointment confirmation based on appointments.confirm permission', function (): void {
        [$tenant] = createPermissionTenant();
        $user = createPermissionUser($tenant);
        $patient = createPermissionPatient($tenant, $user);
        $appointment = createPermissionAppointment($tenant, $patient, $user);

        $this->actingAs($user)
            ->post(route('appointments.confirm', $appointment))
            ->assertForbidden();

        $user->givePermissionTo('appointments.confirm');

        $response = $this->actingAs($user)
            ->post(route('appointments.confirm', $appointment));

        $response->assertRedirectToRoute('appointments.show', $appointment);
        $response->assertSessionHas('success', 'Appointment confirmed successfully.');

        expect($appointment->fresh()->status->value)->toBe('confirmed');
    });
});
