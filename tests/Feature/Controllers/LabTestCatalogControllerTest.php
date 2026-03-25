<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\StaffType;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
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
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createLabCatalogContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Lab Country '.$sequence,
        'country_code' => 'LC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Lab Package '.$sequence,
        'users' => 50 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Lab Tenant '.$sequence,
        'domain' => 'lab-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'LBC'.$sequence,
        'name' => 'Lab Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Main Branch '.$sequence,
        'branch_code' => 'LB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'lab.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $sequence++;

    return [$tenant, $branch, $user];
}

function labReferenceIds(): array
{
    $category = LabTestCategory::query()->where('name', 'Hematology')->firstOrFail();
    $specimenType = SpecimenType::query()->where('name', 'Blood')->firstOrFail();
    $secondarySpecimenType = SpecimenType::query()->where('name', 'Serum')->firstOrFail();
    $resultType = LabResultType::query()->where('code', 'parameter_panel')->firstOrFail();
    $freeEntryResultType = LabResultType::query()->where('code', 'free_entry')->firstOrFail();
    $definedOptionResultType = LabResultType::query()->where('code', 'defined_option')->firstOrFail();

    return [$category, $specimenType, $secondarySpecimenType, $resultType, $freeEntryResultType, $definedOptionResultType];
}

function createLabTestCatalogRecord(
    Tenant $tenant,
    LabTestCategory $category,
    LabResultType $resultType,
    array $specimenTypes,
    array $attributes = [],
): LabTestCatalog {
    $labTest = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => $attributes['test_code'] ?? 'LAB-'.Str::upper(Str::random(6)),
        'test_name' => $attributes['test_name'] ?? 'Lab Test '.Str::upper(Str::random(4)),
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'description' => $attributes['description'] ?? null,
        'base_price' => $attributes['base_price'] ?? 10000,
        'is_active' => $attributes['is_active'] ?? true,
    ]);

    $labTest->specimenTypes()->sync(
        collect($specimenTypes)->map(static fn (SpecimenType $specimenType): string => $specimenType->id)->all(),
    );

    return $labTest->refresh();
}

function createReferencedLabTestWorkflow(
    Tenant $tenant,
    FacilityBranch $branch,
    User $user,
    LabTestCatalog $labTestCatalog,
): void {
    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'LAB-STAFF-'.Str::upper(Str::random(5)),
        'first_name' => 'Lab',
        'last_name' => 'Technician',
        'email' => 'staff-'.Str::lower(Str::random(8)).'@test.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-'.Str::upper(Str::random(6)),
        'first_name' => 'Referenced',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000111',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-'.Str::upper(Str::random(6)),
        'visit_type' => VisitType::OPD_CONSULTATION->value,
        'status' => VisitStatus::REGISTERED->value,
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $requestId = (string) Str::uuid();

    DB::table('lab_requests')->insert([
        'id' => $requestId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_id' => $visit->id,
        'requested_by' => $staff->id,
        'request_date' => now(),
        'priority' => 'routine',
        'status' => 'requested',
        'is_stat' => false,
        'billing_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('lab_request_items')->insert([
        'id' => (string) Str::uuid(),
        'request_id' => $requestId,
        'test_id' => $labTestCatalog->id,
        'status' => 'pending',
        'price' => $labTestCatalog->base_price,
        'actual_cost' => 0,
        'is_external' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('forbids users without lab test catalog view permission and allows authorized index access', function (): void {
    [, $branch, $user] = createLabCatalogContext();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-catalogs.index'))
        ->assertForbidden();

    $user->givePermissionTo('lab_test_catalogs.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-catalogs.index'))
        ->assertOk();
});

it('forbids users without lab test catalog create permission and allows authorized create access', function (): void {
    [, $branch, $user] = createLabCatalogContext();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-catalogs.create'))
        ->assertForbidden();

    $user->givePermissionTo('lab_test_catalogs.create');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-catalogs.create'))
        ->assertOk();
});

it('lists lab tests for authorized users and supports search', function (): void {
    [$tenant, $branch, $user] = createLabCatalogContext();
    [$otherTenant] = createLabCatalogContext();
    [$category, $specimenType, , $resultType, $freeEntryResultType] = labReferenceIds();

    $user->givePermissionTo('lab_test_catalogs.view');

    createLabTestCatalogRecord($tenant, $category, $resultType, [$specimenType], [
        'test_code' => 'LAB-001',
        'test_name' => 'Full Blood Count',
        'base_price' => 20000,
        'is_active' => true,
    ]);

    createLabTestCatalogRecord($tenant, $category, $freeEntryResultType, [$specimenType], [
        'test_code' => 'LAB-002',
        'test_name' => 'Peripheral Smear',
        'base_price' => 10000,
        'is_active' => true,
    ]);

    createLabTestCatalogRecord($otherTenant, $category, $freeEntryResultType, [$specimenType], [
        'test_code' => 'LAB-999',
        'test_name' => 'Foreign Test',
        'base_price' => 0,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-catalogs.index', ['search' => 'LAB-001']));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('lab-test-catalog/index')
            ->where('filters.search', 'LAB-001')
            ->has('labTests.data', 1)
            ->where('labTests.data.0.test_name', 'Full Blood Count')
            ->where('labTests.data.0.category', 'Hematology'));
});

it('creates a lab test catalog entry with lookup-backed relationships', function (): void {
    [, $branch, $user] = createLabCatalogContext();
    [$category, $specimenType, $secondarySpecimenType, $resultType] = labReferenceIds();

    $user->givePermissionTo('lab_test_catalogs.create');

    $payload = [
        'test_code' => 'CBC-001',
        'test_name' => 'Complete Blood Count',
        'lab_test_category_id' => $category->id,
        'specimen_type_ids' => [$specimenType->id, $secondarySpecimenType->id],
        'result_type_id' => $resultType->id,
        'description' => 'Panel for baseline hematology assessment.',
        'base_price' => 25000,
        'is_active' => true,
        'result_parameters' => [
            ['label' => 'WBC', 'unit' => 'x10^9/L', 'reference_range' => '4 - 11', 'value_type' => 'numeric'],
            ['label' => 'Hemoglobin', 'unit' => 'g/dL', 'reference_range' => '12 - 16', 'value_type' => 'numeric'],
        ],
    ];

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('lab-test-catalogs.store'), $payload);

    $response->assertRedirectToRoute('lab-test-catalogs.index');
    $response->assertSessionHas('success', 'Lab test created successfully.');

    $labTest = LabTestCatalog::query()->where('test_code', 'CBC-001')->firstOrFail();

    expect($labTest->test_name)->toBe('Complete Blood Count')
        ->and($labTest->description)->toBe('Panel for baseline hematology assessment.')
        ->and($labTest->category)->toBe('Hematology')
        ->and($labTest->result_capture_type)->toBe('parameter_panel')
        ->and($labTest->specimenTypes()->count())->toBe(2)
        ->and($labTest->resultParameters()->count())->toBe(2);
});

it('updates a lab test catalog entry', function (): void {
    [$tenant, $branch, $user] = createLabCatalogContext();
    [$category, $specimenType, $secondarySpecimenType, $resultType, $freeEntryResultType, $definedOptionResultType] = labReferenceIds();

    $user->givePermissionTo('lab_test_catalogs.update');

    $labTest = createLabTestCatalogRecord($tenant, $category, $freeEntryResultType, [$specimenType], [
        'test_code' => 'LAB-UPD-1',
        'test_name' => 'Malaria Test',
        'base_price' => 15000,
        'is_active' => true,
    ]);

    $payload = [
        'test_code' => 'LAB-UPD-1',
        'test_name' => 'Malaria Microscopy',
        'lab_test_category_id' => $category->id,
        'specimen_type_ids' => [$specimenType->id, $secondarySpecimenType->id],
        'result_type_id' => $definedOptionResultType->id,
        'description' => 'Updated malaria workflow.',
        'base_price' => 18000,
        'is_active' => true,
        'result_options' => [
            ['label' => 'Positive'],
            ['label' => 'Negative'],
        ],
    ];

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->put(route('lab-test-catalogs.update', $labTest), $payload);

    $response->assertRedirectToRoute('lab-test-catalogs.index');
    $response->assertSessionHas('success', 'Lab test updated successfully.');

    expect($labTest->fresh()->test_name)->toBe('Malaria Microscopy')
        ->and((float) $labTest->fresh()->base_price)->toBe(18000.0)
        ->and($labTest->fresh()->description)->toBe('Updated malaria workflow.')
        ->and($labTest->fresh()->result_capture_type)->toBe('defined_option')
        ->and($labTest->fresh()->specimenTypes()->count())->toBe(2)
        ->and($labTest->fresh()->resultOptions()->count())->toBe(2)
        ->and($labTest->fresh()->resultParameters()->count())->toBe(0);
});

it('deletes an unreferenced lab test catalog entry', function (): void {
    [$tenant, $branch, $user] = createLabCatalogContext();
    [$category, $specimenType, , , $freeEntryResultType] = labReferenceIds();

    $user->givePermissionTo('lab_test_catalogs.delete');

    $labTest = createLabTestCatalogRecord($tenant, $category, $freeEntryResultType, [$specimenType], [
        'test_code' => 'LAB-DEL-1',
        'test_name' => 'ESR',
        'base_price' => 5000,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('lab-test-catalogs.destroy', $labTest));

    $response->assertRedirectToRoute('lab-test-catalogs.index');
    $response->assertSessionHas('success', 'Lab test deleted successfully.');
    $this->assertDatabaseMissing('lab_test_catalogs', ['id' => $labTest->id]);
});

it('blocks deleting a referenced lab test catalog entry', function (): void {
    [$tenant, $branch, $user] = createLabCatalogContext();
    [$category, $specimenType, , , $freeEntryResultType] = labReferenceIds();

    $user->givePermissionTo('lab_test_catalogs.delete');

    $labTest = createLabTestCatalogRecord($tenant, $category, $freeEntryResultType, [$specimenType], [
        'test_code' => 'LAB-DEL-2',
        'test_name' => 'Creatinine',
        'base_price' => 8000,
        'is_active' => true,
    ]);

    createReferencedLabTestWorkflow($tenant, $branch, $user, $labTest);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('lab-test-catalogs.destroy', $labTest));

    $response->assertRedirectToRoute('lab-test-catalogs.index');
    $response->assertSessionHas('error', 'This lab test cannot be deleted because it has existing lab requests.');
    $this->assertDatabaseHas('lab_test_catalogs', ['id' => $labTest->id]);
});
