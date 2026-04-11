<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class FacilityManagerController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tenants.view', only: ['dashboard', 'index', 'show']),
        ];
    }

    public function dashboard(Request $request): Response
    {
        Gate::authorize('viewAny', Tenant::class);

        $tenantQuery = Tenant::query()->with('currentSubscription.subscriptionPackage');

        $summaryQuery = (clone $tenantQuery);
        $tenants = $this->tenantsWithCounts((clone $tenantQuery)->orderBy('name'))->get();

        $totalUsers = User::query()
            ->whereNotNull('tenant_id')
            ->count();

        $totalBranches = $tenants->sum('branches_count');

        $followUpTenants = $tenants
            ->filter(static fn (Tenant $tenant): bool => ! $tenant->isOnboardingComplete()
                || $tenant->currentSubscription?->status?->value === 'past_due'
                || $tenant->currentSubscription === null)
            ->sortBy(static fn (Tenant $tenant): string => sprintf(
                '%d-%d-%s',
                $tenant->isOnboardingComplete() ? 1 : 0,
                $tenant->currentSubscription?->status?->value === 'past_due' ? 0 : 1,
                mb_strtolower($tenant->name),
            ))
            ->take(8)
            ->values()
            ->map(fn (Tenant $tenant): array => $this->tenantListPayload($tenant))
            ->all();

        return Inertia::render('facility-manager/dashboard', [
            'metrics' => [
                [
                    'label' => 'Facilities',
                    'value' => (clone $summaryQuery)->count(),
                    'hint' => 'All onboarded and onboarding facility workspaces.',
                ],
                [
                    'label' => 'Onboarded',
                    'value' => (clone $summaryQuery)->whereNotNull('onboarding_completed_at')->count(),
                    'hint' => 'Facilities that have completed the onboarding flow.',
                ],
                [
                    'label' => 'Active Subscriptions',
                    'value' => (clone $summaryQuery)
                        ->whereHas('currentSubscription', static fn (Builder $query): Builder => $query->where('status', 'active'))
                        ->count(),
                    'hint' => 'Facilities currently on an active subscription.',
                ],
                [
                    'label' => 'Needs Follow-Up',
                    'value' => count($followUpTenants),
                    'hint' => 'Facilities missing onboarding or needing subscription attention.',
                ],
                [
                    'label' => 'Users',
                    'value' => $totalUsers,
                    'hint' => 'All tenant-linked users across the platform.',
                ],
                [
                    'label' => 'Branches',
                    'value' => $totalBranches,
                    'hint' => 'Configured branches across all facilities.',
                ],
            ],
            'follow_up_tenants' => $followUpTenants,
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Tenant::class);

        $filters = [
            'search' => $request->string('search')->value() ?: null,
            'onboarding' => $request->string('onboarding')->value() ?: null,
            'subscription' => $request->string('subscription')->value() ?: null,
        ];

        $tenants = $this->tenantsWithCounts($this->filteredTenantQuery($request))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => $this->tenantListPayload($tenant));

        return Inertia::render('facility-manager/index', [
            'filters' => $filters,
            'tenants' => $tenants,
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $tenant->load([
            'country',
            'address',
            'currentSubscription.subscriptionPackage',
            'subscriptions.subscriptionPackage',
            'branches',
            'departments',
            'staff.staff.position',
            'staff.roles',
        ])->loadCount([
            'branches',
            'departments',
            'staff as users_count',
            'patients',
            'visits',
            'labRequests',
        ]);

        $prescriptionsCount = Prescription::query()
            ->join('patient_visits', 'patient_visits.id', '=', 'prescriptions.visit_id')
            ->where('patient_visits.tenant_id', $tenant->id)
            ->count();

        $verifiedUsersCount = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('email_verified_at')
            ->count();

        $recentUsers = $tenant->staff
            ->sortByDesc(static fn (User $user) => $user->created_at?->getTimestamp() ?? 0)
            ->take(8)
            ->values()
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->staff?->position?->name,
                'roles' => $user->roles->pluck('name')->values()->all(),
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
            ])
            ->all();

        $subscriptionHistory = $tenant->subscriptions
            ->sortByDesc(static fn (TenantSubscription $subscription) => $subscription->created_at?->getTimestamp() ?? 0)
            ->take(5)
            ->values()
            ->map(static fn (TenantSubscription $subscription): array => [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
                'package' => $subscription->subscriptionPackage?->name,
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'activated_at' => $subscription->activated_at?->toISOString(),
                'current_period_ends_at' => $subscription->current_period_ends_at?->toISOString(),
                'created_at' => $subscription->created_at?->toISOString(),
            ])
            ->all();

        $usage = [
            'patients' => $tenant->patients_count ?? 0,
            'visits' => $tenant->visits_count ?? 0,
            'lab_requests' => $tenant->lab_requests_count ?? 0,
            'prescriptions' => $prescriptionsCount,
            'verified_users' => $verifiedUsersCount,
            'last_visit_at' => PatientVisit::query()
                ->where('tenant_id', $tenant->id)
                ->max('registered_at'),
            'last_lab_request_at' => LabRequest::query()
                ->where('tenant_id', $tenant->id)
                ->max('request_date'),
            'last_prescription_at' => Prescription::query()
                ->join('patient_visits', 'patient_visits.id', '=', 'prescriptions.visit_id')
                ->where('patient_visits.tenant_id', $tenant->id)
                ->max('prescriptions.created_at'),
        ];

        return Inertia::render('facility-manager/show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'status' => $tenant->status?->value,
                'facility_level' => $tenant->facility_level?->value,
                'onboarding_completed_at' => $tenant->onboarding_completed_at?->toISOString(),
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
                'current_subscription' => $tenant->currentSubscription ? [
                    'id' => $tenant->currentSubscription->id,
                    'status' => $tenant->currentSubscription->status->value,
                    'status_label' => $tenant->currentSubscription->status->label(),
                    'trial_ends_at' => $tenant->currentSubscription->trial_ends_at?->toISOString(),
                    'activated_at' => $tenant->currentSubscription->activated_at?->toISOString(),
                    'current_period_ends_at' => $tenant->currentSubscription->current_period_ends_at?->toISOString(),
                    'package' => $tenant->currentSubscription->subscriptionPackage ? [
                        'name' => $tenant->currentSubscription->subscriptionPackage->name,
                        'price' => $tenant->currentSubscription->subscriptionPackage->price,
                    ] : null,
                ] : null,
                'counts' => [
                    'branches' => $tenant->branches_count ?? 0,
                    'departments' => $tenant->departments_count ?? 0,
                    'users' => $tenant->users_count ?? 0,
                ],
                'branches' => $tenant->branches
                    ->map(static fn ($branch): array => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'branch_code' => $branch->branch_code,
                        'status' => is_string($branch->status) ? $branch->status : $branch->status?->value,
                    ])
                    ->values()
                    ->all(),
                'departments' => $tenant->departments
                    ->map(static fn ($department): array => [
                        'id' => $department->id,
                        'name' => $department->department_name,
                    ])
                    ->values()
                    ->all(),
            ],
            'recent_users' => $recentUsers,
            'subscription_history' => $subscriptionHistory,
            'usage' => $usage,
        ]);
    }

    private function filteredTenantQuery(Request $request): Builder
    {
        $search = $request->string('search')->value();
        $onboarding = $request->string('onboarding')->value();
        $subscription = $request->string('subscription')->value();

        return Tenant::query()
            ->with('currentSubscription.subscriptionPackage')
            ->when(
                is_string($search) && $search !== '',
                static fn (Builder $query): Builder => $query->where(
                    static function (Builder $tenantQuery) use ($search): void {
                        $tenantQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('domain', 'like', "%{$search}%");
                    },
                ),
            )
            ->when(
                $onboarding === 'completed',
                static fn (Builder $query): Builder => $query->whereNotNull('onboarding_completed_at'),
            )
            ->when(
                $onboarding === 'open',
                static fn (Builder $query): Builder => $query->whereNull('onboarding_completed_at'),
            )
            ->when(
                $subscription === 'no_subscription',
                static fn (Builder $query): Builder => $query->whereDoesntHave('currentSubscription'),
            )
            ->when(
                is_string($subscription) && $subscription !== '' && $subscription !== 'no_subscription',
                static fn (Builder $query): Builder => $query->whereHas(
                    'currentSubscription',
                    static fn (Builder $subscriptionQuery): Builder => $subscriptionQuery->where('status', $subscription),
                ),
            );
    }

    private function tenantsWithCounts(Builder $query): Builder
    {
        return $query->withCount([
            'branches',
            'departments',
            'staff as users_count',
            'patients',
            'visits',
            'labRequests',
        ])->selectSub(
            Prescription::query()
                ->selectRaw('COUNT(*)')
                ->join('patient_visits', 'patient_visits.id', '=', 'prescriptions.visit_id')
                ->whereColumn('patient_visits.tenant_id', 'tenants.id'),
            'prescriptions_count',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantListPayload(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'status' => $tenant->status?->value,
            'facility_level' => $tenant->facility_level?->value,
            'onboarding_completed_at' => $tenant->onboarding_completed_at?->toISOString(),
            'current_subscription' => $tenant->currentSubscription ? [
                'status' => $tenant->currentSubscription->status->value,
                'status_label' => $tenant->currentSubscription->status->label(),
                'package' => $tenant->currentSubscription->subscriptionPackage ? [
                    'name' => $tenant->currentSubscription->subscriptionPackage->name,
                ] : null,
            ] : null,
            'counts' => [
                'branches' => $tenant->branches_count ?? 0,
                'departments' => $tenant->departments_count ?? 0,
                'users' => $tenant->users_count ?? 0,
                'patients' => $tenant->patients_count ?? 0,
                'visits' => $tenant->visits_count ?? 0,
                'lab_requests' => $tenant->lab_requests_count ?? 0,
                'prescriptions' => $tenant->prescriptions_count ?? 0,
            ],
        ];
    }
}
