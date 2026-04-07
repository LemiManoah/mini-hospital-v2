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
use App\Models\LabTestResultParameter;
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

function createLabResultPrintContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Print Country '.$sequence,
        'country_code' => 'LP'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Print Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Print Tenant '.$sequence,
        'domain' => 'print-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'LP'.$sequence,
        'name' => 'Print Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Print Branch '.$sequence,
        'branch_code' => 'PB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'LAB-PRINT-'.$sequence,
        'first_name' => 'Lab',
        'last_name' => 'Printer',
        'email' => 'lab.print'.$sequence.'@test.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'lab.print.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-LP-'.$sequence,
        'first_name' => 'Print',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000401',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-LP-'.$sequence,
        'visit_type' => 'outpatient',
        'status' => 'in_progress',
        'registered_at' => now(),
        'registered_by' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $category = LabTestCategory::query()->where('name', 'Hematology')->firstOrFail();
    $specimenType = SpecimenType::query()->where('name', 'Blood')->firstOrFail();
    $resultType = LabResultType::query()->where('code', 'parameter_panel')->firstOrFail();

    $test = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => 'LP-CBC-'.$sequence,
        'test_name' => 'Lab Print CBC '.$sequence,
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 35000,
        'is_active' => true,
    ]);
    $test->specimenTypes()->sync([$specimenType->id]);

    $parameter = LabTestResultParameter::query()->create([
        'lab_test_catalog_id' => $test->id,
        'label' => 'Hemoglobin',
        'unit' => 'g/dL',
        'reference_range' => '12 - 16',
        'value_type' => 'numeric',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $requestId = (string) Str::uuid();
    DB::table('lab_requests')->insert([
        'id' => $requestId,
        'tenant_id' => $tenant->id,
        'facility_branch_id' => $branch->id,
        'visit_id' => $visit->id,
        'requested_by' => $staff->id,
        'request_date' => now(),
        'priority' => 'urgent',
        'status' => 'requested',
        'is_stat' => true,
        'billing_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $requestItem = LabRequestItem::query()->create([
        'request_id' => $requestId,
        'test_id' => $test->id,
        'status' => 'pending',
        'price' => 35000,
        'actual_cost' => 0,
        'is_external' => false,
    ]);

    $sequence++;

    return [$branch, $user, $requestItem, $parameter, $specimenType];
}

it('streams a pdf for a released laboratory result', function (): void {
    [$branch, $user, $requestItem, $parameter, $specimenType] = createLabResultPrintContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.results.store', $requestItem), [
            'result_notes' => 'Printable result note.',
            'parameter_values' => [
                [
                    'lab_test_result_parameter_id' => $parameter->id,
                    'value' => '13.4',
                ],
            ],
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.review', $requestItem), [
            'review_notes' => 'Reviewed for print.',
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.approve', $requestItem), [
            'approval_notes' => 'Released for print.',
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.request-items.print', $requestItem));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('does not allow printing an unreleased laboratory result', function (): void {
    [$branch, $user, $requestItem] = createLabResultPrintContext();

    $user->givePermissionTo('lab_requests.view');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.request-items.print', $requestItem))
        ->assertForbidden();
});
