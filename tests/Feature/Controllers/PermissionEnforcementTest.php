<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Models\Country;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

function createPermissionTestTenant(string $suffix): Tenant
{
    $country = Country::query()->create([
        'country_name' => 'Uganda '.$suffix,
        'country_code' => 'UG'.$suffix,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Starter '.$suffix,
        'users' => 10,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    return Tenant::query()->create([
        'name' => 'Tenant '.$suffix,
        'domain' => 'tenant-'.$suffix,
        'has_branches' => false,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::CLINIC,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);
}

function createPermissionTestUser(?Tenant $tenant = null, bool $isSupport = false): User
{
    return User::factory()->create([
        'tenant_id' => $tenant?->id,
        'email_verified_at' => now(),
        'is_support' => $isSupport,
    ]);
}

it('forbids users index without users view permission', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = createPermissionTestUser();

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertForbidden();
});

it('allows users index with users view permission', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = createPermissionTestUser();
    $user->givePermissionTo('users.view');

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertOk();
});

it('forbids support users from opening facility switcher without tenants view permission', function (): void {
    $this->seed(PermissionSeeder::class);
    createPermissionTestTenant('alpha');

    $supportUser = createPermissionTestUser(isSupport: true);

    $response = $this->actingAs($supportUser)->get(route('facility-switcher.index'));

    $response->assertForbidden();
});

it('allows support users to open facility switcher with tenants view permission', function (): void {
    $this->seed(PermissionSeeder::class);
    createPermissionTestTenant('beta');

    $supportUser = createPermissionTestUser(isSupport: true);
    $supportUser->givePermissionTo('tenants.view');

    $response = $this->actingAs($supportUser)->get(route('facility-switcher.index'));

    $response->assertOk();
});

it('forbids support users from switching tenant context without tenants update permission', function (): void {
    $this->seed(PermissionSeeder::class);
    $tenant = createPermissionTestTenant('gamma');

    $supportUser = createPermissionTestUser(isSupport: true);
    $supportUser->givePermissionTo('tenants.view');

    $response = $this->actingAs($supportUser)->post(route('facility-switcher.switch', $tenant->id));

    $response->assertForbidden();
});

it('allows support users to switch tenant context with tenants update permission', function (): void {
    $this->seed(PermissionSeeder::class);
    $tenant = createPermissionTestTenant('delta');

    $supportUser = createPermissionTestUser(isSupport: true);
    $supportUser->givePermissionTo('tenants.update');

    $response = $this->actingAs($supportUser)->post(route('facility-switcher.switch', $tenant->id));

    $response->assertRedirectToRoute('branch-switcher.index');
    $response->assertSessionHas('success', 'Switched to '.$tenant->name);

    expect($supportUser->fresh()?->tenant_id)->toBe($tenant->id);
});

it('forbids tenant users from onboarding without tenant onboarding permission', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = createPermissionTestTenant('epsilon');
    $tenant->update([
        'onboarding_completed_at' => null,
        'onboarding_current_step' => 'profile',
    ]);

    $user = createPermissionTestUser($tenant);

    $response = $this->actingAs($user)->get(route('onboarding.show'));

    $response->assertForbidden();
});

it('allows tenant admins to onboard with tenant onboarding permission', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = createPermissionTestTenant('zeta');
    $tenant->update([
        'onboarding_completed_at' => null,
        'onboarding_current_step' => 'profile',
    ]);

    $user = createPermissionTestUser($tenant);
    $user->givePermissionTo('tenants.onboard');

    $response = $this->actingAs($user)->get(route('onboarding.show'));

    $response->assertOk();
});
