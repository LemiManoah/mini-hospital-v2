<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\StaffType;
use App\Enums\StockMovementType;
use App\Enums\UnitType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\LabRequestItem;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\SpecimenType;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\InventoryStockLedger;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createLaboratoryWorklistContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Worklist Country '.$sequence,
        'country_code' => 'WC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Worklist Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Worklist Tenant '.$sequence,
        'domain' => 'worklist-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'WL'.$sequence,
        'name' => 'Worklist Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lab Branch '.$sequence,
        'branch_code' => 'WB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $tenant->id,
        'employee_number' => 'LABTECH-'.$sequence,
        'first_name' => 'Lab',
        'last_name' => 'Technician',
        'email' => 'lab.tech'.$sequence.'@test.com',
        'type' => StaffType::MEDICAL,
        'hire_date' => now()->toDateString(),
        'is_active' => true,
    ]);
    $staff->branches()->sync([$branch->id => ['is_primary_location' => true]]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staff->id,
        'email' => 'lab.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $patient = Patient::query()->create([
        'tenant_id' => $tenant->id,
        'patient_number' => 'PAT-WL-'.$sequence,
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000200',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $visit = PatientVisit::query()->create([
        'tenant_id' => $tenant->id,
        'patient_id' => $patient->id,
        'facility_branch_id' => $branch->id,
        'visit_number' => 'VIS-WL-'.$sequence,
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
        'test_code' => 'FBC-'.$sequence,
        'test_name' => 'Full Blood Count '.$sequence,
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 25000,
        'is_active' => true,
    ]);
    $test->specimenTypes()->sync([$specimenType->id]);

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

    $requestItem = LabRequestItem::query()->create([
        'request_id' => $requestId,
        'test_id' => $test->id,
        'status' => 'pending',
        'price' => 25000,
        'actual_cost' => 0,
        'is_external' => false,
    ]);

    $sequence++;

    return [$branch, $user, $requestItem];
}

function seedLaboratoryConsumableStock(
    FacilityBranch $branch,
    User $user,
    InventoryItem $inventoryItem,
    float $quantity,
): InventoryLocation {
    $labLocation = InventoryLocation::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'name' => 'Lab Stock '.$inventoryItem->name,
        'location_code' => 'LAB-'.mb_strtoupper(mb_substr((string) $inventoryItem->id, 0, 6)),
        'type' => InventoryLocationType::LABORATORY,
        'is_active' => true,
    ]);

    $batch = InventoryBatch::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $labLocation->id,
        'inventory_item_id' => $inventoryItem->id,
        'batch_number' => 'LAB-BATCH-001',
        'expiry_date' => now()->addMonths(12)->toDateString(),
        'unit_cost' => $inventoryItem->default_purchase_price ?? 0,
        'quantity_received' => $quantity,
        'received_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    StockMovement::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $labLocation->id,
        'inventory_item_id' => $inventoryItem->id,
        'inventory_batch_id' => $batch->id,
        'movement_type' => StockMovementType::Receipt,
        'quantity' => $quantity,
        'unit_cost' => $inventoryItem->default_purchase_price ?? 0,
        'occurred_at' => now(),
        'created_by' => $user->id,
    ]);

    return $labLocation;
}

it('shows the laboratory worklist to authorized users', function (): void {
    [$branch, $user] = createLaboratoryWorklistContext();

    $user->givePermissionTo('lab_requests.view');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.worklist.index'));

    $response->assertRedirectToRoute('laboratory.incoming.index');
});

it('shows the laboratory dashboard to authorized users', function (): void {
    [$branch, $user] = createLaboratoryWorklistContext();

    $user->givePermissionTo('lab_requests.view');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.dashboard.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/dashboard')
            ->has('metrics', 4)
            ->has('request_status_counts')
            ->has('workflow_stage_counts')
            ->has('recent_requests', 1)
            ->where('metrics.0.value', 1));
});

it('shows inventory consumable defaults on the dedicated consumables page', function (): void {
    [$branch, $user, $requestItem] = createLaboratoryWorklistContext();

    $user->givePermissionTo('lab_requests.view');

    $unit = Unit::query()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Pieces',
        'symbol' => 'pcs',
        'type' => UnitType::COUNT,
    ]);

    InventoryItem::factory()->consumable()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'EDTA Tube',
        'unit_id' => $unit->id,
        'default_purchase_price' => 1500,
    ]);

    InventoryItem::factory()->consumable()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Plain Tube',
        'item_type' => InventoryItemType::SUPPLY,
        'unit_id' => $unit->id,
        'default_purchase_price' => 900,
    ]);

    $inventoryItem = InventoryItem::query()->where('tenant_id', $branch->tenant_id)->where('name', 'EDTA Tube')->firstOrFail();

    seedLaboratoryConsumableStock($branch, $user, $inventoryItem, 12);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.request-items.consumables.show', $requestItem));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/request-item-consumables')
            ->has('consumableOptions', 2)
            ->where('consumableOptions.0.label', 'EDTA Tube | Qty 12.000 pcs')
            ->where('consumableOptions.0.unit_label', 'pcs')
            ->where('consumableOptions.0.default_unit_cost', 1500)
            ->where('consumableOptions.1.label', 'Plain Tube | Qty 0.000 pcs')
            ->where('consumableOptions.1.available_quantity', 0));
});

it('shows any stocked lab item type on the dedicated consumables page', function (): void {
    [$branch, $user, $requestItem] = createLaboratoryWorklistContext();

    $user->givePermissionTo('lab_requests.view');

    $drug = InventoryItem::query()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Lab Reference Tablet',
        'generic_name' => 'Lab Reference Tablet',
        'item_type' => InventoryItemType::DRUG,
        'default_purchase_price' => 500,
        'is_active' => true,
    ]);

    seedLaboratoryConsumableStock($branch, $user, $drug, 4);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.request-items.consumables.show', $requestItem));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/request-item-consumables')
            ->where('consumableOptions.0.name', 'Lab Reference Tablet')
            ->where('consumableOptions.0.available_quantity', 4));
});

it('shows a computed patient age on queue cards when date of birth is available', function (): void {
    [$branch, $user, $requestItem] = createLaboratoryWorklistContext();

    $user->givePermissionTo('lab_requests.view');

    $requestItem->request?->visit?->patient?->forceFill([
        'date_of_birth' => now()->subYears(10)->toDateString(),
        'age' => null,
        'age_units' => null,
    ])->save();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.incoming.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/queue')
            ->where('requests.data.0.visit.patient.display_age', 10)
            ->where('requests.data.0.visit.patient.display_age_units', 'year'));
});

it('records and removes consumable usage while syncing actual cost and stock', function (): void {
    [$branch, $user, $requestItem] = createLaboratoryWorklistContext();

    $user->givePermissionTo(['lab_requests.view', 'lab_requests.update']);

    $unit = Unit::query()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'Pieces',
        'symbol' => 'pcs',
        'type' => UnitType::COUNT,
    ]);

    $inventoryItem = InventoryItem::factory()->consumable()->create([
        'tenant_id' => $branch->tenant_id,
        'name' => 'EDTA Tube',
        'unit_id' => $unit->id,
        'default_purchase_price' => 1500,
    ]);

    $labLocation = seedLaboratoryConsumableStock($branch, $user, $inventoryItem, 12);

    $payload = [
        'inventory_item_id' => $inventoryItem->id,
        'consumable_name' => 'EDTA Tube',
        'unit_label' => 'pcs',
        'quantity' => 2,
        'unit_cost' => 1500,
        'notes' => 'Second tube used because the first sample clotted.',
    ];

    $storeResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('laboratory.request-items.consumables.store', $requestItem), $payload);

    $storeResponse->assertRedirectToRoute('laboratory.request-items.consumables.show', $requestItem);
    $storeResponse->assertSessionHas('success', 'Consumable usage recorded successfully.');

    $requestItem->refresh();

    expect((float) $requestItem->actual_cost)->toBe(3000.0)
        ->and($requestItem->status->value)->toBe('in_progress');

    $labBalanceAfterStore = resolve(InventoryStockLedger::class)
        ->summarizeByLocation($branch->id)
        ->firstWhere('inventory_location_id', $labLocation->id)['quantity'] ?? 0.0;

    expect((float) $labBalanceAfterStore)->toBe(10.0);

    $usage = DB::table('lab_request_item_consumables')
        ->where('lab_request_item_id', $requestItem->id)
        ->first();

    expect($usage)->not()->toBeNull();

    $deleteResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('laboratory.request-items.consumables.destroy', [
            'labRequestItem' => $requestItem,
            'labRequestItemConsumable' => $usage->id,
        ]));

    $deleteResponse->assertRedirectToRoute('laboratory.request-items.consumables.show', $requestItem);
    $deleteResponse->assertSessionHas('success', 'Consumable usage removed successfully.');

    $labBalanceAfterDelete = resolve(InventoryStockLedger::class)
        ->summarizeByLocation($branch->id)
        ->firstWhere('inventory_location_id', $labLocation->id)['quantity'] ?? 0.0;

    expect((float) $requestItem->fresh()->actual_cost)->toBe(0.0)
        ->and((float) $labBalanceAfterDelete)->toBe(12.0);
});
