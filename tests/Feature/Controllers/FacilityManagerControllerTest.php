<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\TenantSupportPriority;
use App\Enums\TenantSupportStatus;
use App\Models\Country;
use App\Models\FacilityBranch;
use App\Models\SubscriptionPackage;
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
            ->where('tenants.data.0.id', $tenant->id)
            ->where('tenants.data.0.support_workflow.status', TenantSupportStatus::STABLE->value)
            ->where('tenants.data.0.support_workflow.priority', TenantSupportPriority::NORMAL->value));
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
            ->where('tenant.counts.branches', 1)
            ->where('tenant.support_workflow.status', TenantSupportStatus::STABLE->value));
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
        ->get(route('facility-manager.facilities.audit', $tenant))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/audit')
            ->where('tenant.id', $tenant->id)
            ->where('health.summary.total_checks', 11));

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

    $followUpAt = now()->addDay()->format('Y-m-d H:i:s');
    $lastContactedAt = now()->format('Y-m-d H:i:s');

    $this->actingAs($supportUser)
        ->patch(route('facility-manager.facilities.support-workflow.update', $tenant), [
            'status' => TenantSupportStatus::ESCALATED->value,
            'priority' => TenantSupportPriority::URGENT->value,
            'follow_up_at' => $followUpAt,
            'last_contacted_at' => $lastContactedAt,
        ])
        ->assertRedirect(route('facility-manager.facilities.notes', $tenant))
        ->assertSessionHas('success', 'Support workflow updated for '.$tenant->name.'.');

    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'support_status' => TenantSupportStatus::ESCALATED->value,
        'support_priority' => TenantSupportPriority::URGENT->value,
        'support_follow_up_at' => $followUpAt,
        'support_last_contacted_at' => $lastContactedAt,
    ]);

    $exportResponse = $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.export', [
            'support' => TenantSupportStatus::ESCALATED->value,
        ]));

    $exportResponse->assertOk();

    expect((string) $exportResponse->headers->get('content-type'))->toContain('text/csv');
    expect($exportResponse->streamedContent())
        ->toContain('Support Status')
        ->toContain($tenant->name)
        ->toContain(TenantSupportStatus::ESCALATED->label())
        ->toContain(TenantSupportPriority::URGENT->label());
});

it('allows support users with tenants.update permission to create a facility from facility manager', function (): void {
    $this->seed(PermissionSeeder::class);

    $package = SubscriptionPackage::factory()->create();
    $country = Country::factory()->create();
    $supportUser = User::factory()->create([
        'tenant_id' => null,
        'is_support' => true,
        'email_verified_at' => now(),
    ]);
    $supportUser->givePermissionTo('tenants.view');
    $supportUser->givePermissionTo('tenants.update');

    $this->actingAs($supportUser)
        ->get(route('facility-manager.facilities.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('facility-manager/create')
            ->has('facilityLevels')
            ->has('subscriptionPackages')
            ->has('countries'));

    $response = $this->actingAs($supportUser)
        ->post(route('facility-manager.facilities.store'), [
            'owner_name' => 'Grace Hopper',
            'workspace_name' => 'Support Created Hospital',
            'email' => 'support-created@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subscription_package_id' => $package->id,
            'facility_level' => 'hospital',
            'country_id' => $country->id,
            'domain' => 'support-created-hospital',
        ]);

    $tenant = Tenant::query()
        ->where('name', 'Support Created Hospital')
        ->firstOrFail();

    $response->assertRedirect(route('facility-manager.facilities.show', $tenant))
        ->assertSessionHas('success', 'Facility created successfully. Use impersonation to continue onboarding when needed.');

    $this->assertAuthenticatedAs($supportUser);
    $this->assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'name' => 'Support Created Hospital',
        'subscription_package_id' => $package->id,
    ]);
    $this->assertDatabaseHas('users', [
        'tenant_id' => $tenant->id,
        'email' => 'support-created@example.com',
    ]);
    $this->assertDatabaseHas('tenant_subscriptions', [
        'tenant_id' => $tenant->id,
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
