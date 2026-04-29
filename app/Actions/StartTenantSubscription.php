<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Carbon\CarbonInterface;

final readonly class StartTenantSubscription
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(
        Tenant $tenant,
        SubscriptionPackage $package,
        ?User $actor = null,
        int $trialDays = 14,
    ): TenantSubscription {
        $now = now();

        $subscription = TenantSubscription::query()->create([
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

        $this->recordAuditActivity->handle(
            logName: 'support',
            event: 'tenant.subscription.started',
            subject: $subscription,
            description: 'Tenant trial subscription started.',
            actor: $actor,
            tenantId: $tenant->id,
            newValues: [
                'subscription_id' => $subscription->id,
                'subscription_package_id' => $package->id,
                'status' => SubscriptionStatus::TRIAL->value,
                'starts_at' => $this->dateIsoString($subscription, 'starts_at'),
                'trial_ends_at' => $this->dateIsoString($subscription, 'trial_ends_at'),
            ],
            metadata: [
                'source' => 'workspace_registration',
                'trial_days' => $trialDays,
            ],
        );

        return $subscription;
    }

    public function markPendingActivation(TenantSubscription $subscription, ?User $actor = null): TenantSubscription
    {
        $oldStatus = $this->statusValue($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::PENDING_ACTIVATION,
            'updated_by' => $actor?->id,
            'meta' => [
                ...$this->metaArray($subscription),
                'pending_activation_at' => now()->toIso8601String(),
            ],
        ]);

        $subscription->refresh();

        $this->recordAuditActivity->handle(
            logName: 'support',
            event: 'tenant.subscription.pending_activation',
            subject: $subscription,
            description: 'Tenant subscription marked pending activation.',
            actor: $actor,
            tenantId: $subscription->tenant_id,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'subscription_id' => $subscription->id,
                'status' => SubscriptionStatus::PENDING_ACTIVATION->value,
            ],
        );

        return $subscription;
    }

    public function markActive(TenantSubscription $subscription, ?User $actor = null, int $billingDays = 30): TenantSubscription
    {
        $now = now();
        $oldStatus = $this->statusValue($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'activated_at' => $subscription->activated_at ?? $now,
            'current_period_starts_at' => $now,
            'current_period_ends_at' => $now->copy()->addDays($billingDays),
            'updated_by' => $actor?->id,
            'meta' => [
                ...$this->metaArray($subscription),
                'activated_via' => 'checkout_callback',
                'activated_at' => $now->toIso8601String(),
            ],
        ]);

        $subscription->refresh();

        $this->recordAuditActivity->handle(
            logName: 'support',
            event: 'tenant.subscription.activated',
            subject: $subscription,
            description: 'Tenant subscription activated.',
            actor: $actor,
            tenantId: $subscription->tenant_id,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'subscription_id' => $subscription->id,
                'status' => SubscriptionStatus::ACTIVE->value,
                'activated_at' => $this->dateIsoString($subscription, 'activated_at'),
                'current_period_starts_at' => $this->dateIsoString($subscription, 'current_period_starts_at'),
                'current_period_ends_at' => $this->dateIsoString($subscription, 'current_period_ends_at'),
            ],
            metadata: [
                'billing_days' => $billingDays,
            ],
        );

        return $subscription;
    }

    public function markFailed(TenantSubscription $subscription, ?User $actor = null): TenantSubscription
    {
        $oldStatus = $this->statusValue($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'updated_by' => $actor?->id,
            'meta' => [
                ...$this->metaArray($subscription),
                'checkout_failed_at' => now()->toIso8601String(),
            ],
        ]);

        $subscription->refresh();

        $this->recordAuditActivity->handle(
            logName: 'support',
            event: 'tenant.subscription.past_due',
            subject: $subscription,
            description: 'Tenant subscription marked past due.',
            actor: $actor,
            tenantId: $subscription->tenant_id,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'subscription_id' => $subscription->id,
                'status' => SubscriptionStatus::PAST_DUE->value,
            ],
        );

        return $subscription;
    }

    /**
     * @return array<string, mixed>
     */
    private function metaArray(TenantSubscription $subscription): array
    {
        $meta = $subscription->getAttributeValue('meta');

        if (! is_array($meta)) {
            return [];
        }

        $normalizedMeta = [];

        foreach ($meta as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $normalizedMeta[$key] = $value;
        }

        return $normalizedMeta;
    }

    private function statusValue(TenantSubscription $subscription): ?string
    {
        $status = $subscription->getAttributeValue('status');

        if ($status instanceof SubscriptionStatus) {
            return $status->value;
        }

        return is_string($status) ? $status : null;
    }

    private function dateIsoString(TenantSubscription $subscription, string $attribute): ?string
    {
        $date = $subscription->getAttributeValue($attribute);

        return $date instanceof CarbonInterface ? $date->toISOString() : null;
    }
}
