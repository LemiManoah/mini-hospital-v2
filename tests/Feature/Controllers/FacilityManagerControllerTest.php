<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

it('allows support users with tenants.view permission to open the facility manager dashboard and facilities list', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = Tenant::factory()->create();
    FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $supportUser = User::factory()->create([
        'tenant_id' => null,
        'is_support' => true,
        'email_verified_at' => now(),
    ]);
    $supportUser->givePermissionTo('tenants.view');

    $this->actingAs($supportUser)
        ->get(route('facility-manager.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/dashboard')
            ->has('metrics', 6));

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/index')
            ->where('tenants.data.0.id', $tenant->id));
});

it('serializes enum-cast subscription statuses on the facility manager facilities list', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = Tenant::factory()->create();
    FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    TenantSubscription::query()->create([
        'tenant_id' => $tenant->id,
        'subscription_package_id' => $tenant->subscription_package_id,
        'status' => SubscriptionStatus::ACTIVE,
        'starts_at' => now()->subMonth(),
        'activated_at' => now()->subWeek(),
        'current_period_starts_at' => now()->subWeek(),
        'current_period_ends_at' => now()->addWeeks(3),
    ]);

    $supportUser = User::factory()->create([
        'tenant_id' => null,
        'is_support' => true,
        'email_verified_at' => now(),
    ]);
    $supportUser->givePermissionTo('tenants.view');

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/index')
            ->where('tenants.data.0.id', $tenant->id)
            ->where('tenants.data.0.current_subscription.status', SubscriptionStatus::ACTIVE->value)
            ->where('tenants.data.0.current_subscription.status_label', SubscriptionStatus::ACTIVE->label()));
});

it('allows support users with tenants.view permission to open a facility manager detail page', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = Tenant::factory()->create();
    FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $supportUser = User::factory()->create([
        'tenant_id' => null,
        'is_support' => true,
        'email_verified_at' => now(),
    ]);
    $supportUser->givePermissionTo('tenants.view');

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.show', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/show')
            ->where('tenant.id', $tenant->id)
            ->where('tenant.counts.branches', 1));
});

it('allows support users to open facility manager child pages and record support notes', function (): void {
    $this->seed(PermissionSeeder::class);

    $tenant = Tenant::factory()->create();
    FacilityBranch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $supportUser = User::factory()->create([
        'tenant_id' => null,
        'is_support' => true,
        'email_verified_at' => now(),
    ]);
    $supportUser->givePermissionTo('tenants.view');
    $supportUser->givePermissionTo('tenants.update');

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.branches', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/branches')
            ->where('tenant.id', $tenant->id));

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.users', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/users')
            ->where('tenant.id', $tenant->id));

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.subscriptions', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/subscriptions')
            ->where('tenant.id', $tenant->id));

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.activity', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/activity')
            ->where('tenant.id', $tenant->id));

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.notes', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/support-notes')
            ->where('tenant.id', $tenant->id));

    $this->actingAs($supportUser)
        ->post(route('facility-manager.facilities.notes.store', $tenant), [
            'title' => 'Billing follow-up',
            'body' => 'Facility requested package review before activation.',
            'is_pinned' => true,
        ])
        ->assertRedirect(route('facility-manager.facilities.notes', $tenant));

    $this->assertDatabaseHas('tenant_support_notes', [
        'tenant_id' => $tenant->id,
        'author_id' => $supportUser->id,
        'title' => 'Billing follow-up',
        'is_pinned' => true,
    ]);
});

it('forbids non-support users from opening the facility manager', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create([
        'tenant_id' => null,
        'is_support' => false,
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('tenants.view');

    $this->actingAs($user)
        ->get(route('facility-manager.dashboard'))
        ->assertForbidden();
});
