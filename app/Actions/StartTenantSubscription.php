<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;

final class StartTenantSubscription
{
    public function handle(
        Tenant $tenant,
        SubscriptionPackage $package,
        ?User $actor = null,
        int $trialDays = 14,
    ): TenantSubscription {
        $now = now();

        return TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_package_id' => $package->id,
            'status' => SubscriptionStatus::TRIAL,
            'starts_at' => $now,
            'trial_ends_at' => $now->copy()->addDays($trialDays),
            'current_period_starts_at' => $now,
            'current_period_ends_at' => $now->copy()->addDays($trialDays),
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
            'meta' => [
                'source' => 'workspace_registration',
                'trial_days' => $trialDays,
            ],
        ]);
    }

    public function markPendingActivation(TenantSubscription $subscription, ?User $actor = null): TenantSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::PENDING_ACTIVATION,
            'updated_by' => $actor?->id,
            'meta' => [
                ...($subscription->meta ?? []),
                'pending_activation_at' => now()->toIso8601String(),
            ],
        ]);

        return $subscription->fresh();
    }

    public function markActive(TenantSubscription $subscription, ?User $actor = null, int $billingDays = 30): TenantSubscription
    {
        $now = now();

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'activated_at' => $subscription->activated_at ?? $now,
            'current_period_starts_at' => $now,
            'current_period_ends_at' => $now->copy()->addDays($billingDays),
            'updated_by' => $actor?->id,
            'meta' => [
                ...($subscription->meta ?? []),
                'activated_via' => 'checkout_callback',
                'activated_at' => $now->toIso8601String(),
            ],
        ]);

        return $subscription->fresh();
    }

    public function markFailed(TenantSubscription $subscription, ?User $actor = null): TenantSubscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'updated_by' => $actor?->id,
            'meta' => [
                ...($subscription->meta ?? []),
                'checkout_failed_at' => now()->toIso8601String(),
            ],
        ]);

        return $subscription->fresh();
    }
}
