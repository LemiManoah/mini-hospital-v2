<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\Supplier;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createSupplierTestContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Supplier Test Country '.$sequence,
        'country_code' => 'SC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Supplier Test Package '.$sequence,
        'users' => 30 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Supplier Test Tenant '.$sequence,
        'domain' => 'supplier-test-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'SC'.$sequence.'X',
        'name' => 'Supplier Test Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Supplier Test Branch '.$sequence,
        'branch_code' => 'SB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'supplier.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $sequence++;

    return [$tenant, $branch, $user];
}

it('lists suppliers for authorized user', function (): void {
    [$tenant, $branch, $user] = createSupplierTestContext();
    $user->givePermissionTo('suppliers.view');

    Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Test Medical Supplies',
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('suppliers.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/suppliers/index')
            ->has('suppliers.data', 1));
});

it('denies supplier index without permission', function (): void {
    [, $branch, $user] = createSupplierTestContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('suppliers.index'));

    $response->assertForbidden();
});

it('creates a supplier', function (): void {
    [, $branch, $user] = createSupplierTestContext();
    $user->givePermissionTo(['suppliers.view', 'suppliers.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('suppliers.store'), [
            'name' => 'New Medical Supplier',
            'contact_person' => 'John Doe',
            'email' => 'john@supplier.test',
            'phone' => '+256700000000',
            'is_active' => true,
        ]);

    $response->assertRedirectToRoute('suppliers.index');

    $supplier = Supplier::query()->where('name', 'New Medical Supplier')->first();
    expect($supplier)->not->toBeNull()
        ->and($supplier->contact_person)->toBe('John Doe');
});

it('requires name to create supplier', function (): void {
    [, $branch, $user] = createSupplierTestContext();
    $user->givePermissionTo('suppliers.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->from(route('suppliers.create'))
        ->post(route('suppliers.store'), [
            'email' => 'test@test.com',
        ]);

    $response->assertRedirect(route('suppliers.create'))
        ->assertSessionHasErrors('name');
});

it('updates a supplier', function (): void {
    [$tenant, $branch, $user] = createSupplierTestContext();
    $user->givePermissionTo(['suppliers.view', 'suppliers.update']);

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Name',
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->put(route('suppliers.update', $supplier), [
            'name' => 'Updated Supplier Name',
            'is_active' => true,
        ]);

    $response->assertRedirectToRoute('suppliers.index');
    expect($supplier->fresh()->name)->toBe('Updated Supplier Name');
});

it('deletes a supplier', function (): void {
    [$tenant, $branch, $user] = createSupplierTestContext();
    $user->givePermissionTo('suppliers.delete');

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'To Delete',
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('suppliers.destroy', $supplier));

    $response->assertRedirectToRoute('suppliers.index');
    $this->assertSoftDeleted($supplier);
});

it('searches suppliers', function (): void {
    [$tenant, $branch, $user] = createSupplierTestContext();
    $user->givePermissionTo('suppliers.view');

    Supplier::query()->create(['tenant_id' => $tenant->id, 'name' => 'Alpha Pharma', 'is_active' => true]);
    Supplier::query()->create(['tenant_id' => $tenant->id, 'name' => 'Beta Supplies', 'is_active' => true]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('suppliers.index', ['search' => 'Alpha']));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('suppliers.data', 1)
            ->where('suppliers.data.0.name', 'Alpha Pharma'));
});
