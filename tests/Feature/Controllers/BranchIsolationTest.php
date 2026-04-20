<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
use App\Models\DoctorSchedule;
use App\Models\FacilityBranch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createTenantWithBranches(int $count = 2): array
{
    static $sequence = 1;
    $suffix = Str::lower(Str::random(6));

    $country = Country::query()->create([
        'country_name' => 'Uganda '.$suffix,
        'country_code' => 'CT'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $currency = Currency::query()->create([
        'code' => 'C'.mb_str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
        'name' => 'Currency '.$suffix,
        'symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Starter Package '.$suffix,
        'users' => $sequence + 1,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'City General Hospital '.$suffix,
        'domain' => 'city-general-'.$suffix,
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $branches = collect();
    for ($i = 1; $i <= $count; $i++) {
        $branches->push(FacilityBranch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Branch '.$i,
            'branch_code' => mb_strtoupper(mb_substr($suffix, 0, 3)).$i,
            'currency_id' => $currency->id,
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => $i === 1,
            'has_store' => true,
        ]));
    }

    $sequence++;

    return [$tenant, $branches];
}

it('redirects tenant users to branch switcher when multiple branches and none selected', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'MED-001',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@hospital.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'john.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo('facility_branches.view');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirectToRoute('branch-switcher.index');
});

it('allows tenant admins to open facility branch administration without an active branch selected', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'ADM-001',
        'first_name' => 'Alice',
        'last_name' => 'Admin',
        'email' => 'alice@hospital.com',
        'type' => StaffType::ADMINISTRATIVE,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'alice.admin@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo('facility_branches.view');

    $response = $this->actingAs($user)->get(route('facility-branches.index'));

    $response->assertOk();
});

it('allows switching to an authorized branch and stores it in session', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'NUR-001',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@hospital.com',
        'type' => StaffType::NURSING,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'jane.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['facility_branches.view', 'facility_branches.update']);

    $response = $this->actingAs($user)->post(route('branch-switcher.switch', $branches[0]->id));

    $response->assertRedirectToRoute('dashboard');
    $response->assertSessionHas('active_branch_id', $branches[0]->id);
});

it('forbids switching to an inactive branch', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();
    $branches[1]->update(['status' => GeneralStatus::INACTIVE]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'NUR-002',
        'first_name' => 'Grace',
        'last_name' => 'Nurse',
        'email' => 'grace@hospital.com',
        'type' => StaffType::NURSING,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'grace.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['facility_branches.view', 'facility_branches.update']);

    $response = $this->actingAs($user)->post(route('branch-switcher.switch', $branches[1]->id));

    $response->assertForbidden();
});

it('forbids opening an appointment from a different active branch', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'MED-002',
        'first_name' => 'Brian',
        'last_name' => 'Doctor',
        'email' => 'brian@hospital.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'brian.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['appointments.view', 'facility_branches.view', 'facility_branches.update']);

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-BR-001',
        'first_name' => 'Cross',
        'last_name' => 'Branch',
        'gender' => 'female',
        'phone_number' => '+256700000001',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $appointment = Appointment::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branches[1]->id,
        'patient_id' => $patient->id,
        'appointment_date' => now()->toDateString(),
        'start_time' => '09:00:00',
        'reason_for_visit' => 'Review visit',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $response = $this
        ->withSession(['active_branch_id' => $branches[0]->id])
        ->actingAs($user)
        ->get(route('appointments.show', $appointment));

    $response->assertForbidden();
});

it('forbids opening a visit from a different active branch', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'MED-003',
        'first_name' => 'Carol',
        'last_name' => 'Clinician',
        'email' => 'carol@hospital.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'carol.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['visits.view', 'facility_branches.view', 'facility_branches.update']);

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-BR-002',
        'first_name' => 'Visit',
        'last_name' => 'Branch',
        'gender' => 'male',
        'phone_number' => '+256700000002',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branches[1]->id,
        'patient_id' => $patient->id,
        'visit_number' => 'VIS-BR-001',
        'visit_type' => 'opd_consultation',
        'status' => VisitStatus::REGISTERED,
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $response = $this
        ->withSession(['active_branch_id' => $branches[0]->id])
        ->actingAs($user)
        ->get(route('visits.show', $visit));

    $response->assertForbidden();
});

it('forbids editing a clinic from a different active branch', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $department = Department::query()->create([
        'tenant_id' => $tenant->id,
        'department_code' => 'DEP-BR-001',
        'department_name' => 'Outpatient',
        'is_clinical' => true,
        'is_active' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'ADM-002',
        'first_name' => 'Clinic',
        'last_name' => 'Admin',
        'email' => 'clinic.admin@hospital.com',
        'type' => StaffType::ADMINISTRATIVE,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $staff->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'clinic.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['clinics.view', 'clinics.update', 'facility_branches.view', 'facility_branches.update']);

    $clinic = Clinic::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branches[1]->id,
        'clinic_code' => 'CL-BR-001',
        'clinic_name' => 'Branch Two Clinic',
        'department_id' => $department->id,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $response = $this
        ->withSession(['active_branch_id' => $branches[0]->id])
        ->actingAs($user)
        ->get(route('clinics.edit', $clinic));

    $response->assertForbidden();
});

it('forbids editing a doctor schedule from a different active branch', function (): void {
    $this->seed(PermissionSeeder::class);

    [$tenant, $branches] = createTenantWithBranches();

    $department = Department::query()->create([
        'tenant_id' => $tenant->id,
        'department_code' => 'DEP-BR-002',
        'department_name' => 'Consultation',
        'is_clinical' => true,
        'is_active' => true,
    ]);

    $clinic = Clinic::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branches[1]->id,
        'clinic_code' => 'CL-BR-002',
        'clinic_name' => 'Specialist Clinic',
        'department_id' => $department->id,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $doctor = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'MED-004',
        'first_name' => 'Schedule',
        'last_name' => 'Doctor',
        'email' => 'schedule.doctor@hospital.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $doctor->branches()->sync([
        $branches[0]->id => ['is_primary_location' => true],
        $branches[1]->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $doctor->id,
        'email' => 'schedule.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->givePermissionTo(['doctor_schedules.view', 'doctor_schedules.update', 'facility_branches.view', 'facility_branches.update']);

    $schedule = DoctorSchedule::query()->create([
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branches[1]->id,
        'doctor_id' => $doctor->id,
        'clinic_id' => $clinic->id,
        'day_of_week' => 'monday',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'slot_duration_minutes' => 30,
        'max_patients' => 10,
        'valid_from' => now()->toDateString(),
        'is_active' => true,
    ]);

    $response = $this
        ->withSession(['active_branch_id' => $branches[0]->id])
        ->actingAs($user)
        ->get(route('appointments.schedules.edit', $schedule));

    $response->assertForbidden();
});

it('forbids non-support users from opening the facility switcher', function (): void {
    $user = User::query()->create([
        'tenant_id' => null,
        'email' => 'plain.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $response = $this->actingAs($user)->get(route('facility-manager.dashboard'));

    $response->assertForbidden();
});

it('forbids non-support users from switching facility context', function (): void {
    [$tenant] = createTenantWithBranches();

    $user = User::query()->create([
        'tenant_id' => null,
        'email' => 'plain.switch.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $response = $this->actingAs($user)->post(route('facility-manager.facilities.switch', $tenant->id));

    $response->assertForbidden();
});

it('allows support users to switch tenant context and clears active branch selection', function (): void {
    $this->seed(PermissionSeeder::class);

    [$sourceTenant, $branches] = createTenantWithBranches();
    [$targetTenant] = createTenantWithBranches();

    $supportUser = User::query()->create([
        'tenant_id' => $sourceTenant->id,
        'email' => 'support.user@hospital.com',
        'password' => Hash::make('password'),
        'is_support' => true,
    ]);
    $supportUser->forceFill(['email_verified_at' => now()])->save();
    $supportUser->givePermissionTo('tenants.update');

    $response = $this
        ->withSession(['active_branch_id' => $branches[0]->id])
        ->actingAs($supportUser)
        ->post(route('facility-manager.facilities.switch', $targetTenant->id));

    $response->assertRedirectToRoute('branch-switcher.index');
    $response->assertSessionMissing('active_branch_id');
    $response->assertSessionHas('success', 'Switched to '.$targetTenant->name);

    $this->assertDatabaseHas('users', [
        'id' => $supportUser->id,
        'tenant_id' => $targetTenant->id,
    ]);
});
