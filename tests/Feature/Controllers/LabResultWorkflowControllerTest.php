<?php

declare(strict_types=1);

use App\Enums\AttendanceType;
use App\Enums\ConsciousLevel;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\MobilityStatus;
use App\Enums\StaffType;
use App\Enums\TriageGrade;
use App\Models\Consultation;
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
use App\Models\TenantGeneralSetting;
use App\Models\TriageRecord;
use App\Models\User;
use App\Models\VisitPayer;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createLabResultWorkflowContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Workflow Country '.$sequence,
        'country_code' => 'RW'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Workflow Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Workflow Tenant '.$sequence,
        'domain' => 'workflow-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'WF'.$sequence,
        'name' => 'Workflow Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Workflow Branch '.$sequence,
        'branch_code' => 'RB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'LAB-WORK-'.$sequence,
        'first_name' => 'Lab',
        'last_name' => 'Reviewer',
        'email' => 'lab.workflow'.$sequence.'@test.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'lab.workflow.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-RW-'.$sequence,
        'first_name' => 'Workflow',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000400',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-RW-'.$sequence,
        'visit_type' => 'opd_consultation',
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
        'test_code' => 'CBC-'.$sequence,
        'test_name' => 'Complete Blood Count '.$sequence,
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

    return [$branch, $user, $requestItem, $parameter];
}

function collectWorkflowSample(FacilityBranch $branch, User $user, LabRequestItem $requestItem): void
{
    $specimenType = $requestItem->test()->firstOrFail()->specimenTypes()->firstOrFail();

    test()->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
}

function storeWorkflowResult(
    FacilityBranch $branch,
    User $user,
    LabRequestItem $requestItem,
    LabTestResultParameter $parameter,
    string $value,
    string $notes = 'Sample quality acceptable.',
): void {
    test()->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.results.store', $requestItem), [
            'result_notes' => $notes,
            'parameter_values' => [
                [
                    'lab_test_result_parameter_id' => $parameter->id,
                    'value' => $value,
                ],
            ],
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
}

function approveWorkflowResult(
    FacilityBranch $branch,
    User $user,
    LabRequestItem $requestItem,
    string $reviewNotes = 'Reviewed against analyzer output.',
    string $approvalNotes = 'Released to clinician.',
): void {
    test()->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.approve', $requestItem), [
            'review_notes' => $reviewNotes,
            'approval_notes' => $approvalNotes,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
}

function prepareClinicianWorkspaceContext(LabRequestItem $requestItem, User $user): PatientVisit
{
    $request = $requestItem->request()->firstOrFail();
    $visit = $request->visit()->firstOrFail();

    $visit->forceFill([
        'doctor_id' => $user->staff_id,
        'status' => 'in_progress',
    ])->save();

    if (! $visit->payer()->exists()) {
        VisitPayer::query()->create([
            'tenant_id' => $visit->tenant_id,
            'patient_visit_id' => $visit->id,
            'billing_type' => 'cash',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    if (! $visit->triage()->exists()) {
        TriageRecord::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'nurse_id' => $user->staff_id,
            'triage_datetime' => now(),
            'triage_grade' => TriageGrade::GREEN->value,
            'attendance_type' => AttendanceType::NEW->value,
            'conscious_level' => ConsciousLevel::ALERT->value,
            'mobility_status' => MobilityStatus::INDEPENDENT->value,
            'chief_complaint' => 'Headache',
        ]);
    }

    if (! $visit->consultation()->exists()) {
        Consultation::query()->create([
            'tenant_id' => $visit->tenant_id,
            'facility_branch_id' => $visit->facility_branch_id,
            'visit_id' => $visit->id,
            'doctor_id' => $user->staff_id,
            'started_at' => now(),
            'chief_complaint' => 'Headache',
            'assessment' => 'Clinical review started',
        ]);
    }

    return $visit->refresh();
}

it('picks a sample for a laboratory request item from the incoming queue', function (): void {
    [$branch, $user, $requestItem] = createLabResultWorkflowContext();

    $user->givePermissionTo('lab_requests.update');
    $specimenType = $requestItem->test()->firstOrFail()->specimenTypes()->firstOrFail();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
            'outside_sample_origin' => 'Referral clinic',
        ]);

    $response->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    $response->assertSessionHas('success', 'Sample picked successfully.');

    $requestItem->refresh();

    expect($requestItem->status->value)->toBe('pending')
        ->and($requestItem->received_at)->not()->toBeNull()
        ->and(DB::table('lab_specimens')->where('lab_request_item_id', $requestItem->id)->value('outside_sample'))->toBe(1)
        ->and(DB::table('lab_requests')->where('id', $requestItem->request_id)->value('status'))->toBe('sample_collected');
});

it('marks a collected sample as received and moves the request item into processing', function (): void {
    [$branch, $user, $requestItem] = createLabResultWorkflowContext();

    $user->givePermissionTo('lab_requests.update');
    $specimenType = $requestItem->test()->firstOrFail()->specimenTypes()->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.receive', $requestItem));

    $response->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    $response->assertSessionHas('success', 'Lab request item received successfully.');

    $requestItem->refresh();

    expect($requestItem->status->value)->toBe('in_progress')
        ->and($requestItem->received_at)->not()->toBeNull()
        ->and(DB::table('lab_requests')->where('id', $requestItem->request_id)->value('status'))->toBe('in_progress');
});

it('stores reviews and approves parameter-panel lab results', function (): void {
    [$branch, $user, $requestItem, $parameter] = createLabResultWorkflowContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);
    $specimenType = $requestItem->test()->firstOrFail()->specimenTypes()->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.results.store', $requestItem), [
            'result_notes' => 'Sample quality acceptable.',
            'parameter_values' => [
                [
                    'lab_test_result_parameter_id' => $parameter->id,
                    'value' => '13.4',
                ],
            ],
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $requestItem->refresh();

    expect($requestItem->result_entered_at)->not()->toBeNull()
        ->and($requestItem->workflow_stage)->toBe('result_entered');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.review', $requestItem), [
            'review_notes' => 'Reviewed against analyzer output.',
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.approve', $requestItem), [
            'approval_notes' => 'Released to clinician.',
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $requestItem->refresh();

    expect($requestItem->status->value)->toBe('completed')
        ->and($requestItem->approved_at)->not()->toBeNull()
        ->and($requestItem->result_visible)->toBeTrue()
        ->and(DB::table('lab_requests')->where('id', $requestItem->request_id)->value('status'))->toBe('completed');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.request-items.show', $requestItem))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('laboratory/request-item')
            ->where('labRequestItem.result_visible', true)
            ->where('labRequestItem.workflow_stage', 'approved')
            ->has('labRequestItem.result_entry.values', 1));
});

it('can review and release parameter-panel lab results in one approval step', function (): void {
    [$branch, $user, $requestItem, $parameter] = createLabResultWorkflowContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);
    $specimenType = $requestItem->test()->firstOrFail()->specimenTypes()->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.results.store', $requestItem), [
            'result_notes' => 'Sample quality acceptable.',
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
        ->post(route('laboratory.request-items.approve', $requestItem), [
            'review_notes' => 'Reviewed against analyzer output.',
            'approval_notes' => 'Released to clinician.',
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem)
        ->assertSessionHas(
            'success',
            'Lab results reviewed, approved, and released successfully.',
        );

    $requestItem->refresh();
    $resultEntry = DB::table('lab_result_entries')
        ->where('lab_request_item_id', $requestItem->id)
        ->first();

    expect($requestItem->reviewed_at)->not()->toBeNull()
        ->and($requestItem->approved_at)->not()->toBeNull()
        ->and($requestItem->status->value)->toBe('completed')
        ->and($requestItem->result_visible)->toBeTrue();

    expect($resultEntry?->reviewed_at)->not()->toBeNull();
});

it('auto releases reviewed results when approval is not required by general settings', function (): void {
    [$branch, $user, $requestItem, $parameter] = createLabResultWorkflowContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);

    TenantGeneralSetting::query()->updateOrCreate(
        [
            'tenant_id' => $branch->tenant_id,
            'key' => 'laboratory.require_approval_before_release',
        ],
        [
            'value' => '0',
        ],
    );

    collectWorkflowSample($branch, $user, $requestItem);
    storeWorkflowResult($branch, $user, $requestItem, $parameter, '13.4');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.review', $requestItem), [
            'review_notes' => 'Reviewed and released from the review step.',
        ]);

    $response->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    $response->assertSessionHas('success', 'Lab results reviewed and released successfully.');

    $requestItem->refresh();
    $resultEntry = $requestItem->resultEntry()->firstOrFail();

    expect($requestItem->status->value)->toBe('completed')
        ->and($requestItem->approved_at)->not()->toBeNull()
        ->and($requestItem->result_visible)->toBeTrue()
        ->and($resultEntry->approved_at)->not()->toBeNull()
        ->and($resultEntry->released_at)->not()->toBeNull();
});

it('moves a request item between the incoming and enter-results queues after sample picking', function (): void {
    [$branch, $user, $requestItem] = createLabResultWorkflowContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);
    $specimenType = $requestItem->test()->firstOrFail()->specimenTypes()->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.incoming.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('laboratory/queue')
            ->where('page.stage', 'incoming')
            ->has('requests.data', 1)
            ->has('requests.data.0.items', 1));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.collect-sample', $requestItem), [
            'specimen_type_id' => $specimenType->id,
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.enter-results.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('laboratory/queue')
            ->where('page.stage', 'enter_results')
            ->has('requests.data', 1)
            ->has('requests.data.0.items', 1));
});

it('corrects a released result, records the audit reason, and requires release again', function (): void {
    [$branch, $user, $requestItem, $parameter] = createLabResultWorkflowContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);

    collectWorkflowSample($branch, $user, $requestItem);
    storeWorkflowResult($branch, $user, $requestItem, $parameter, '13.4');
    approveWorkflowResult($branch, $user, $requestItem);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.correct', $requestItem), [
            'correction_reason' => 'Analyzer decimal point was entered incorrectly.',
            'result_notes' => 'Corrected after bench reconciliation.',
            'parameter_values' => [
                [
                    'lab_test_result_parameter_id' => $parameter->id,
                    'value' => '11.4',
                ],
            ],
        ]);

    $response->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    $response->assertSessionHas(
        'success',
        'Lab result correction saved. Review and release it again before clinicians can see it.',
    );

    $requestItem->refresh();
    $resultEntry = $requestItem->resultEntry()->with('values')->firstOrFail();

    expect($requestItem->status->value)->toBe('in_progress')
        ->and($requestItem->workflow_stage)->toBe('result_entered')
        ->and($requestItem->approved_at)->toBeNull()
        ->and($requestItem->completed_at)->toBeNull()
        ->and($requestItem->result_visible)->toBeFalse()
        ->and($resultEntry->corrected_at)->not->toBeNull()
        ->and($resultEntry->correction_reason)->toBe('Analyzer decimal point was entered incorrectly.')
        ->and($resultEntry->values->first()?->value_numeric)->toBe(11.4)
        ->and(DB::table('lab_requests')->where('id', $requestItem->request_id)->value('status'))->toBe('in_progress');

    approveWorkflowResult(
        $branch,
        $user,
        $requestItem,
        reviewNotes: 'Correction reviewed against analyzer rerun.',
        approvalNotes: 'Corrected result released.',
    );

    $requestItem->refresh();

    expect($requestItem->status->value)->toBe('completed')
        ->and($requestItem->approved_at)->not->toBeNull()
        ->and($requestItem->result_visible)->toBeTrue();
});

it('hides unreleased and corrected-again results from the visit workspace until release', function (): void {
    [$branch, $user, $requestItem, $parameter] = createLabResultWorkflowContext();

    $user->givePermissionTo(['visits.view', 'lab_requests.update']);
    $visit = prepareClinicianWorkspaceContext($requestItem, $user);

    collectWorkflowSample($branch, $user, $requestItem);
    storeWorkflowResult($branch, $user, $requestItem, $parameter, '13.4');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('visit/show')
            ->where('visit.lab_requests.0.items.0.result_visible', false)
            ->where('visit.lab_requests.0.items.0.result_entry', null));

    approveWorkflowResult($branch, $user, $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('visit/show')
            ->where('visit.lab_requests.0.items.0.result_visible', true)
            ->has('visit.lab_requests.0.items.0.result_entry.values', 1));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.correct', $requestItem), [
            'correction_reason' => 'Reference sample confirmed a different value.',
            'result_notes' => 'Corrected after quality control review.',
            'parameter_values' => [
                [
                    'lab_test_result_parameter_id' => $parameter->id,
                    'value' => '12.1',
                ],
            ],
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('visit/show')
            ->where('visit.lab_requests.0.items.0.result_visible', false)
            ->where('visit.lab_requests.0.items.0.result_entry', null));
});

it('hides unreleased and corrected-again results from the consultation workspace until release', function (): void {
    [$branch, $user, $requestItem, $parameter] = createLabResultWorkflowContext();

    $user->givePermissionTo(['consultations.view', 'lab_requests.update']);
    $visit = prepareClinicianWorkspaceContext($requestItem, $user);

    collectWorkflowSample($branch, $user, $requestItem);
    storeWorkflowResult($branch, $user, $requestItem, $parameter, '13.4');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('doctors.consultations.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('doctor/consultations/show')
            ->where('visit.lab_requests.0.items.0.result_visible', false)
            ->where('visit.lab_requests.0.items.0.result_entry', null));

    approveWorkflowResult($branch, $user, $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('doctors.consultations.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('doctor/consultations/show')
            ->where('visit.lab_requests.0.items.0.result_visible', true)
            ->has('visit.lab_requests.0.items.0.result_entry.values', 1));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.correct', $requestItem), [
            'correction_reason' => 'Quality control repeat required a corrected value.',
            'result_notes' => 'Corrected before final clinician release.',
            'parameter_values' => [
                [
                    'lab_test_result_parameter_id' => $parameter->id,
                    'value' => '12.7',
                ],
            ],
        ])
        ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('doctors.consultations.show', $visit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('doctor/consultations/show')
            ->where('visit.lab_requests.0.items.0.result_visible', false)
            ->where('visit.lab_requests.0.items.0.result_entry', null));
});
