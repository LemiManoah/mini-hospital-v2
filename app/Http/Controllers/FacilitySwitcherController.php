<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartTenantSubscription;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\SwitchTenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilitySwitcherController implements HasMiddleware
{
    public function __construct(
        private SwitchTenantContext $switchTenantContext,
        private StartTenantSubscription $startTenantSubscription,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:tenants.view', only: ['index', 'show']),
            new Middleware('permission:tenants.update', only: ['switch', 'activateSubscription', 'markSubscriptionPastDue', 'completeOnboarding', 'reopenOnboarding']),
        ];
    }

    public function index(): Response
    {
        Gate::authorize('viewAny', Tenant::class);

        $tenants = Tenant::query()
            ->with([
                'country',
                'currentSubscription.subscriptionPackage',
            ])
            ->withCount(['branches', 'departments', 'staff'])
            ->orderBy('name')
            ->get();

        return Inertia::render('facility-switcher/index', [
            'tenants' => $tenants->map(fn (Tenant $tenant): array => $this->tenantPayload($tenant)),
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $tenant->load([
            'country',
            'address',
            'subscriptionPackage',
            'currentSubscription.subscriptionPackage',
            'branches',
            'departments',
            'staff.staff.position',
        ])->loadCount(['branches', 'departments', 'staff']);

        return Inertia::render('facility-switcher/show', [
            'tenant' => $this->tenantPayload($tenant, true),
        ]);
    }

    public function switch(Request $request, string $tenantId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenant = Tenant::query()->findOrFail($tenantId);

        Gate::authorize('update', $tenant);

        $this->switchTenantContext->handle($request, $user, $tenant->id);

        return to_route('branch-switcher.index');
    }

    public function activateSubscription(Request $request, Tenant $tenant): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('update', $tenant);

        $subscription = $tenant->currentSubscription()->first();

        abort_unless($subscription instanceof TenantSubscription, 404, 'No current subscription record was found.');

        $this->startTenantSubscription->markActive($subscription, $user);

        return back()->with('success', 'Subscription activated for '.$tenant->name.'.');
    }

    public function markSubscriptionPastDue(Request $request, Tenant $tenant): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Gate::authorize('update', $tenant);

        $subscription = $tenant->currentSubscription()->first();

        abort_unless($subscription instanceof TenantSubscription, 404, 'No current subscription record was found.');

        $this->startTenantSubscription->markFailed($subscription, $user);

        return back()->with('success', 'Subscription marked as past due for '.$tenant->name.'.');
    }

    public function completeOnboarding(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        return back()->with('success', 'Onboarding marked complete for '.$tenant->name.'.');
    }

    public function reopenOnboarding(Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);

        $tenant->update([
            'onboarding_completed_at' => null,
        ]);

        return back()->with('success', 'Onboarding reopened for '.$tenant->name.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantPayload(Tenant $tenant, bool $includeDetails = false): array
    {
        $currentSubscription = $tenant->currentSubscription;

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'status' => $tenant->status?->value,
            'facility_level' => $tenant->facility_level?->value,
            'onboarding_completed_at' => $tenant->onboarding_completed_at,
            'country' => $tenant->country ? [
                'country_name' => $tenant->country->country_name,
            ] : null,
            'address' => $tenant->address ? [
                'display_name' => implode(', ', array_filter([
                    $tenant->address->city,
                    $tenant->address->district,
                    $tenant->address->state,
                ])),
            ] : null,
            'subscription_package' => $tenant->subscriptionPackage ? [
                'name' => $tenant->subscriptionPackage->name,
            ] : null,
            'current_subscription' => $currentSubscription ? [
                'id' => $currentSubscription->id,
                'status' => $currentSubscription->status->value,
                'status_label' => $currentSubscription->status->label(),
                'trial_ends_at' => $currentSubscription->trial_ends_at,
                'activated_at' => $currentSubscription->activated_at,
                'current_period_ends_at' => $currentSubscription->current_period_ends_at,
                'package' => $currentSubscription->subscriptionPackage ? [
                    'name' => $currentSubscription->subscriptionPackage->name,
                    'price' => $currentSubscription->subscriptionPackage->price,
                ] : null,
            ] : null,
            'counts' => [
                'branches' => $tenant->branches_count ?? 0,
                'departments' => $tenant->departments_count ?? 0,
                'staff' => $tenant->staff_count ?? 0,
            ],
            'branches' => $includeDetails
                ? $tenant->branches->map(fn (FacilityBranch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'branch_code' => $branch->branch_code,
                ])->values()->all()
                : [],
            'departments' => $includeDetails
                ? $tenant->departments->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => $department->department_name,
                ])->values()->all()
                : [],
            'staff' => $includeDetails
                ? $tenant->staff->map(fn (User $staffUser): array => [
                    'id' => $staffUser->id,
                    'name' => $staffUser->name,
                    'email' => $staffUser->email,
                    'position' => $staffUser->staff?->position?->name,
                ])->values()->all()
                : [],
        ];
    }
}
