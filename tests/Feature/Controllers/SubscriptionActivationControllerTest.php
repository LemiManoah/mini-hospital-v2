<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

it('shows the subscription activation page with a serialized package payload', function (): void {
    $this->seed(PermissionSeeder::class);

    $package = SubscriptionPackage::factory()->create([
        'name' => 'Growth',
        'price' => 125000,
        'users' => 120,
    ]);

    $tenant = Tenant::factory()->create([
        'subscription_package_id' => $package->id,
    ]);

    $subscription = TenantSubscription::query()->create([
        'tenant_id' => $tenant->id,
        'subscription_package_id' => $package->id,
        'status' => SubscriptionStatus::PENDING_ACTIVATION,
        'starts_at' => now()->subDay(),
        'trial_ends_at' => now()->addDays(13),
        'current_period_starts_at' => now()->subDay(),
        'current_period_ends_at' => now()->addDays(13),
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $user->givePermissionTo('tenants.manage_subscription');

    $this->actingAs($user)
        ->get(route('subscription.activate.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('subscription/activate')
            ->where('tenant.id', $tenant->id)
            ->where('subscription.id', $subscription->id)
            ->where('subscription.status', SubscriptionStatus::PENDING_ACTIVATION->value)
            ->where('subscription.status_label', SubscriptionStatus::PENDING_ACTIVATION->label())
            ->where('subscription.package.id', $package->id)
            ->where('subscription.package.name', 'Growth')
            ->where('subscription.package.users', 120)
            ->where('subscription.package.price', '125000.00'));
});

it('moves a subscription to pending activation while preserving existing meta entries', function (): void {
    $this->seed(PermissionSeeder::class);

    $package = SubscriptionPackage::factory()->create();
    $tenant = Tenant::factory()->create([
        'subscription_package_id' => $package->id,
    ]);

    $subscription = TenantSubscription::query()->create([
        'tenant_id' => $tenant->id,
        'subscription_package_id' => $package->id,
        'status' => SubscriptionStatus::TRIAL,
        'starts_at' => now()->subDay(),
        'trial_ends_at' => now()->addDays(13),
        'current_period_starts_at' => now()->subDay(),
        'current_period_ends_at' => now()->addDays(13),
        'meta' => [
            'source' => 'workspace_registration',
            'trial_days' => 14,
        ],
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $user->givePermissionTo('tenants.manage_subscription');

    $this->actingAs($user)
        ->post(route('subscription.activate.store'))
        ->assertRedirect(route('subscription.checkout.show'))
        ->assertSessionHas('success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::PENDING_ACTIVATION)
        ->and($subscription->checkout_provider)->toBe('manual_placeholder')
        ->and($subscription->checkout_reference)->toStartWith('SUB-')
        ->and($subscription->checkout_url)->toBe(route('subscription.checkout.show'))
        ->and($subscription->updated_by)->toBe($user->id)
        ->and($subscription->meta)->toMatchArray([
            'source' => 'workspace_registration',
            'trial_days' => 14,
            'pending_activation_at' => now()->toIso8601String(),
            'checkout_requested_at' => now()->toIso8601String(),
        ]);
});
