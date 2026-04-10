<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
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
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createLabIsolationContext(): array
{
    $country = Country::query()->create([
        'country_name' => 'Isolation Country',
        'country_code' => 'IC',
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Isolation Package',
        'users' => 100,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Isolation Tenant',
        'domain' => 'isolation.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'ISO',
        'name' => 'Isolation Currency',
        'symbol' => 'USh',
    ]);

    $branchA = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Branch A',
        'branch_code' => 'BRA',
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $branchB = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Branch B',
        'branch_code' => 'BRB',
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => false,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'STF-ISO',
        'first_name' => 'Isolation',
        'last_name' => 'Staff',
        'email' => 'isolation.staff@test.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    // Staff belongs to both branches
    $staff->branches()->sync([
        $branchA->id => ['is_primary_location' => true],
        $branchB->id => ['is_primary_location' => false],
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'isolation.user@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();
    $user->assignRole('admin'); // Assuming admin has all lab permissions

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-ISO',
        'first_name' => 'Isolation',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000000',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visitA = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branchA->id,
        'visit_number' => 'VIS-A',
        'visit_type' => 'opd_consultation',
        'status' => 'in_progress',
        'registered_at' => now(),
        'registered_by' => $user->id,
    ]);

    PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branchB->id,
        'visit_number' => 'VIS-B',
        'visit_type' => 'opd_consultation',
        'status' => 'in_progress',
        'registered_at' => now(),
        'registered_by' => $user->id,
    ]);

    $category = LabTestCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Isolation Category',
    ]);
    $specimenType = SpecimenType::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Isolation Specimen',
    ]);
    $resultType = LabResultType::query()->where('code', 'parameter_panel')->firstOrFail();

    $test = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => 'ISO-TEST',
        'test_name' => 'Isolation Test',
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 1000,
        'is_active' => true,
    ]);
    $test->specimenTypes()->sync([$specimenType->id]);

    // Create Lab Request in Branch A
    $requestAId = (string) Str::uuid();
    DB::table('lab_requests')->insert([
        'id' => $requestAId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branchA->id,
        'visit_id' => $visitA->id,
        'requested_by' => $staff->id,
        'request_date' => now(),
        'priority' => 'routine',
        'status' => 'requested',
        'billing_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $requestItemA = LabRequestItem::query()->create([
        'request_id' => $requestAId,
        'test_id' => $test->id,
        'status' => 'pending',
        'price' => 1000,
    ]);

    return [$tenant, $branchA, $branchB, $user, $requestItemA, $specimenType];
}

test('lab requests from Branch A are not visible when Branch B is active', function (): void {
    [$tenant, $branchA, $branchB, $user, $requestItemA] = createLabIsolationContext();

    // Active Branch A: Should see the request
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchA->id])
        ->get(route('laboratory.incoming.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $requestItemA->request_id)
        );

    // Active Branch B: Should NOT see the request
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchB->id])
        ->get(route('laboratory.incoming.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 0)
        );
});

test('lab request items from Branch A are not accessible when Branch B is active', function (): void {
    [$tenant, $branchA, $branchB, $user, $requestItemA] = createLabIsolationContext();

    // Active Branch A: Should be able to view details
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchA->id])
        ->get(route('laboratory.request-items.show', $requestItemA))
        ->assertStatus(200);

    // Active Branch B: Should be forbidden
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchB->id])
        ->get(route('laboratory.request-items.show', $requestItemA))
        ->assertStatus(403);
});

test('lab workflow actions from Branch A are not allowed when Branch B is active', function (): void {
    [$tenant, $branchA, $branchB, $user, $requestItemA, $specimenType] = createLabIsolationContext();

    // Active Branch B: Attempt to collect sample for Branch A request
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchB->id])
        ->post(route('laboratory.request-items.collect-sample', $requestItemA), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertStatus(403);
});

test('specimen and result records follow branch isolation of their lab request', function (): void {
    [$tenant, $branchA, $branchB, $user, $requestItemA, $specimenType] = createLabIsolationContext();

    // 1. Collect sample in Branch A
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchA->id])
        ->post(route('laboratory.request-items.collect-sample', $requestItemA), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirect();

    $requestItemA->refresh();
    $specimen = $requestItemA->specimen;
    expect($specimen)->not->toBeNull();

    // 2. Try to access the request item (which now has specimen) from Branch B
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchB->id])
        ->get(route('laboratory.request-items.show', $requestItemA))
        ->assertStatus(403);

    // 3. Try to enter results from Branch B
    $this->actingAs($user)
        ->withSession(['active_branch_id' => $branchB->id])
        ->post(route('laboratory.request-items.results.store', $requestItemA), [
            'result_notes' => 'Test result',
            'parameter_values' => [],
        ])
        ->assertStatus(403);
});
