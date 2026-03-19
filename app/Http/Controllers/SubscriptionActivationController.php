<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartTenantSubscription;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SubscriptionActivationController
{
    public function __construct(
        private StartTenantSubscription $startTenantSubscription,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant === null) {
            return to_route('home');
        }

        Gate::authorize('manageSubscription', $user->tenant);

        $subscription = $user->tenant->currentSubscription()
            ->with('subscriptionPackage')
            ->first();

        if (! $subscription instanceof TenantSubscription) {
            return to_route('home');
        }

        return Inertia::render('subscription/activate', [
            'tenant' => [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
            ],
            'subscription' => $this->subscriptionPayload($subscription),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant === null) {
            return to_route('home');
        }

        Gate::authorize('manageSubscription', $user->tenant);

        $subscription = $user->tenant->currentSubscription()->first();

        if (! $subscription instanceof TenantSubscription) {
            return to_route('home')->with('error', 'No active subscription record was found.');
        }

        $this->startTenantSubscription->markPendingActivation($subscription, $user);

        $subscription->update([
            'checkout_provider' => 'manual_placeholder',
            'checkout_reference' => sprintf('SUB-%s', now()->format('YmdHis')),
            'checkout_url' => route('subscription.checkout.show'),
            'updated_by' => $user->id,
            'meta' => [
                ...($subscription->meta ?? []),
                'checkout_requested_at' => now()->toIso8601String(),
            ],
        ]);

        return to_route('subscription.checkout.show')->with(
            'success',
            'Subscription moved to pending activation. Continue through checkout to finish activation.',
        );
    }

    public function checkout(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant === null) {
            return to_route('home');
        }

        Gate::authorize('manageSubscription', $user->tenant);

        $subscription = $user->tenant->currentSubscription()
            ->with('subscriptionPackage')
            ->first();

        if (! $subscription instanceof TenantSubscription) {
            return to_route('home');
        }

        return Inertia::render('subscription/checkout', [
            'tenant' => [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
            ],
            'subscription' => $this->subscriptionPayload($subscription),
        ]);
    }

    public function success(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant === null) {
            return to_route('home');
        }

        Gate::authorize('manageSubscription', $user->tenant);

        $subscription = $user->tenant->currentSubscription()->first();

        if (! $subscription instanceof TenantSubscription) {
            return to_route('home')->with('error', 'No subscription record was found for activation.');
        }

        $this->startTenantSubscription->markActive($subscription, $user);

        $subscription->update([
            'checkout_provider' => $subscription->checkout_provider ?? 'manual_placeholder',
            'checkout_url' => route('subscription.checkout.show'),
            'updated_by' => $user->id,
            'meta' => [
                ...($subscription->meta ?? []),
                'checkout_completed_at' => now()->toIso8601String(),
            ],
        ]);

        return to_route('modules')->with(
            'success',
            'Subscription activated successfully. The tenant is now in an active billing period.',
        );
    }

    public function failure(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant === null) {
            return to_route('home');
        }

        Gate::authorize('manageSubscription', $user->tenant);

        $subscription = $user->tenant->currentSubscription()->first();

        if (! $subscription instanceof TenantSubscription) {
            return to_route('home')->with('error', 'No subscription record was found for checkout recovery.');
        }

        $this->startTenantSubscription->markFailed($subscription, $user);

        $subscription->update([
            'checkout_provider' => $subscription->checkout_provider ?? 'manual_placeholder',
            'checkout_url' => route('subscription.checkout.show'),
            'updated_by' => $user->id,
            'meta' => [
                ...($subscription->meta ?? []),
                'checkout_failed_at' => now()->toIso8601String(),
            ],
        ]);

        return to_route('subscription.activate.show')->with(
            'error',
            'Checkout was marked as failed. You can retry activation when ready.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionPayload(TenantSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'status' => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            'starts_at' => $subscription->starts_at,
            'trial_ends_at' => $subscription->trial_ends_at,
            'activated_at' => $subscription->activated_at,
            'current_period_starts_at' => $subscription->current_period_starts_at,
            'current_period_ends_at' => $subscription->current_period_ends_at,
            'checkout_provider' => $subscription->checkout_provider,
            'checkout_reference' => $subscription->checkout_reference,
            'checkout_url' => $subscription->checkout_url,
            'package' => [
                'id' => $subscription->subscriptionPackage->id,
                'name' => $subscription->subscriptionPackage->name,
                'users' => $subscription->subscriptionPackage->users,
                'price' => $subscription->subscriptionPackage->price,
            ],
        ];
    }
}
