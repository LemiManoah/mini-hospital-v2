<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\LabResultType;
use App\Models\LabTestCatalog;
use App\Models\LabTestCategory;
use App\Models\SpecimenType;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createLabLookupContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Lookup Country '.$sequence,
        'country_code' => 'LK'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Lookup Package '.$sequence,
        'users' => 30 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Lookup Tenant '.$sequence,
        'domain' => 'lookup-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'LKU'.$sequence,
        'name' => 'Lookup Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lookup Branch '.$sequence,
        'branch_code' => 'LB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'lookup.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $sequence++;

    return [$tenant, $branch, $user];
}

it('creates and lists tenant lab test categories', function (): void {
    [, $branch, $user] = createLabLookupContext();

    $user->givePermissionTo([
        'lab_test_categories.view',
        'lab_test_categories.create',
    ]);

    $createResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('lab-test-categories.store'), [
            'name' => 'Molecular Testing',
            'description' => 'PCR and related molecular assays.',
            'is_active' => true,
        ]);

    $createResponse->assertRedirectToRoute('lab-test-categories.index');
    $createResponse->assertSessionHas('success', 'Lab test category created successfully.');

    $indexResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-categories.index', ['search' => 'Molecular']));

    $indexResponse->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('lab-test-category/index')
            ->where('filters.search', 'Molecular')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Molecular Testing')
            ->where('categories.data.0.tenant_id', $user->tenant_id));
});

it('prevents editing default lab test categories', function (): void {
    [, $branch, $user] = createLabLookupContext();
    $defaultCategory = LabTestCategory::query()->whereNull('tenant_id')->firstOrFail();

    $user->givePermissionTo('lab_test_categories.update');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('lab-test-categories.edit', $defaultCategory));

    $response->assertRedirectToRoute('lab-test-categories.index');
    $response->assertSessionHas('error', 'Default lab test categories cannot be edited.');
});

it('updates and deletes a tenant specimen type', function (): void {
    [$tenant, $branch, $user] = createLabLookupContext();

    $user->givePermissionTo([
        'specimen_types.view',
        'specimen_types.create',
        'specimen_types.update',
        'specimen_types.delete',
    ]);

    $specimenType = SpecimenType::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Synovial Fluid',
        'description' => 'Joint aspirate specimen.',
        'is_active' => true,
    ]);

    $updateResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->put(route('specimen-types.update', $specimenType), [
            'name' => 'Pleural Fluid',
            'description' => 'Pleural aspirate specimen.',
            'is_active' => true,
        ]);

    $updateResponse->assertRedirectToRoute('specimen-types.index');
    $updateResponse->assertSessionHas('success', 'Specimen type updated successfully.');

    expect($specimenType->fresh()->name)->toBe('Pleural Fluid');

    $deleteResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('specimen-types.destroy', $specimenType));

    $deleteResponse->assertRedirectToRoute('specimen-types.index');
    $deleteResponse->assertSessionHas('success', 'Specimen type deleted successfully.');
    $this->assertDatabaseMissing('specimen_types', ['id' => $specimenType->id]);
});

it('creates and lists result types with code search', function (): void {
    [, $branch, $user] = createLabLookupContext();

    $user->givePermissionTo([
        'result_types.view',
        'result_types.create',
    ]);

    $createResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('result-types.store'), [
            'code' => 'semi_quantitative',
            'name' => 'Semi Quantitative',
            'description' => 'Used for strip-based or tiered result capture.',
            'is_active' => true,
        ]);

    $createResponse->assertRedirectToRoute('result-types.index');
    $createResponse->assertSessionHas('success', 'Result type created successfully.');

    $indexResponse = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('result-types.index', ['search' => 'semi_quantitative']));

    $indexResponse->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('result-type/index')
            ->where('filters.search', 'semi_quantitative')
            ->has('resultTypes.data', 1)
            ->where('resultTypes.data.0.code', 'semi_quantitative'));
});

it('blocks deleting a result type that is already used by a lab test', function (): void {
    [$tenant, $branch, $user] = createLabLookupContext();

    $user->givePermissionTo('result_types.delete');

    $category = LabTestCategory::query()->whereNull('tenant_id')->firstOrFail();
    $specimenType = SpecimenType::query()->whereNull('tenant_id')->firstOrFail();
    $resultType = LabResultType::query()->create([
        'tenant_id' => $tenant->id,
        'code' => 'narrative_'.$tenant->id,
        'name' => 'Narrative Result',
        'description' => null,
        'is_active' => true,
    ]);

    $labTest = LabTestCatalog::query()->create([
        'tenant_id' => $tenant->id,
        'test_code' => 'LRT-001',
        'test_name' => 'Narrative Panel',
        'lab_test_category_id' => $category->id,
        'result_type_id' => $resultType->id,
        'base_price' => 12000,
        'is_active' => true,
    ]);
    $labTest->specimenTypes()->sync([$specimenType->id]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('result-types.destroy', $resultType));

    $response->assertRedirectToRoute('result-types.index');
    $response->assertSessionHas('error', 'This result type cannot be deleted because it is already used by lab tests.');
    $this->assertDatabaseHas('result_types', ['id' => $resultType->id]);
});
