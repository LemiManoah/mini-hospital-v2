<?php

declare(strict_types=1);

use App\Actions\StartTenantSubscription;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;

it('marks a tenant subscription pending activation while preserving existing meta', function (): void {
    $tenant = Tenant::factory()->create();
    $package = SubscriptionPackage::factory()->create();
    $actor = User::factory()->create([
        'tenant_id' => $tenant->id,
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

    $updated = resolve(StartTenantSubscription::class)->markPendingActivation($subscription, $actor);

    expect($updated->status)->toBe(SubscriptionStatus::PENDING_ACTIVATION)
        ->and($updated->updated_by)->toBe($actor->id)
        ->and($updated->meta)->toMatchArray([
            'source' => 'workspace_registration',
            'trial_days' => 14,
            'pending_activation_at' => now()->toIso8601String(),
        ]);
});
