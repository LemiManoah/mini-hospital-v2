<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartTenantSubscription;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\StoreTenantSupportNoteRequest;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\FacilityServiceOrder;
use App\Models\LabRequest;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantSupportNote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilityManagerController implements HasMiddleware
{
    public function __construct(
        private StartTenantSubscription $startTenantSubscription,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:tenants.view', only: [
                'dashboard',
                'index',
                'show',
                'branches',
                'users',
                'subscriptions',
                'activity',
                'notes',
            ]),
            new Middleware('permission:tenants.update', only: [
                'storeNote',
                'activateSubscription',
                'markSubscriptionPastDue',
                'completeOnboarding',
                'reopenOnboarding',
            ]),
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
        ])->loadCount([
            'branches',
            'departments',
            'staff as users_count',
            'patients',
            'visits',
            'labRequests',
            'supportNotes',
        ]);

        $prescriptionsCount = $this->prescriptionsQueryForTenant($tenant->id)->count();
        $verifiedUsersCount = $this->verifiedUsersQueryForTenant($tenant->id)->count();

        $recentUsers = User::query()
            ->where('tenant_id', $tenant->id)
            ->with(['staff.position', 'roles'])->latest()
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => $this->userPayload($user))
            ->all();

        $subscriptionHistory = TenantSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->with('subscriptionPackage')->latest()
            ->limit(5)
            ->get()
            ->map(fn (TenantSubscription $subscription): array => $this->subscriptionPayload($subscription))
            ->all();

        $usage = [
            'patients' => $tenant->patients_count ?? 0,
            'visits' => $tenant->visits_count ?? 0,
            'lab_requests' => $tenant->lab_requests_count ?? 0,
            'prescriptions' => $prescriptionsCount,
            'verified_users' => $verifiedUsersCount,
            'support_notes' => $tenant->support_notes_count ?? 0,
            'last_visit_at' => PatientVisit::query()
                ->where('tenant_id', $tenant->id)
                ->max('registered_at'),
            'last_lab_request_at' => LabRequest::query()
                ->where('tenant_id', $tenant->id)
                ->max('request_date'),
            'last_prescription_at' => $this->prescriptionsQueryForTenant($tenant->id)
                ->max('prescriptions.created_at'),
            'last_support_note_at' => TenantSupportNote::query()
                ->where('tenant_id', $tenant->id)
                ->max('created_at'),
        ];

        return Inertia::render('facility-manager/show', [
            'tenant' => [
                ...$this->tenantSummaryPayload($tenant),
                'counts' => [
                    'branches' => $tenant->branches_count ?? 0,
                    'departments' => $tenant->departments_count ?? 0,
                    'users' => $tenant->users_count ?? 0,
                ],
                'branches' => $tenant->branches
                    ->map(static fn (FacilityBranch $branch): array => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'branch_code' => $branch->branch_code,
                        'status' => is_string($branch->status) ? $branch->status : $branch->status?->value,
                    ])
                    ->values()
                    ->all(),
                'departments' => $tenant->departments
                    ->map(static fn (Department $department): array => [
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

    public function branches(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $branches = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->with(['address', 'currency'])
            ->withCount('staff')
            ->orderByDesc('is_main_branch')
            ->orderBy('name')
            ->get();

        return Inertia::render('facility-manager/branches', [
            'tenant' => $this->tenantSummaryPayload($tenant),
            'metrics' => [
                [
                    'label' => 'Total Branches',
                    'value' => $branches->count(),
                    'hint' => 'All configured facility branches for this tenant.',
                ],
                [
                    'label' => 'Active Branches',
                    'value' => $branches->filter(fn (FacilityBranch $branch): bool => $branch->status?->value === 'active')->count(),
                    'hint' => 'Branches currently marked active.',
                ],
                [
                    'label' => 'Main Branches',
                    'value' => $branches->where('is_main_branch', true)->count(),
                    'hint' => 'Primary branch workspaces.',
                ],
                [
                    'label' => 'Store Enabled',
                    'value' => $branches->where('has_store', true)->count(),
                    'hint' => 'Branches configured with a store location.',
                ],
            ],
            'branches' => $branches
                ->map(static fn (FacilityBranch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'branch_code' => $branch->branch_code,
                    'status' => is_string($branch->status) ? $branch->status : $branch->status?->value,
                    'is_main_branch' => $branch->is_main_branch,
                    'has_store' => $branch->has_store,
                    'staff_count' => $branch->staff_count ?? 0,
                    'currency' => $branch->currency?->code,
                    'address' => implode(', ', array_filter([
                        $branch->address?->city,
                        $branch->address?->district,
                        $branch->address?->state,
                    ])),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function users(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $filters = [
            'search' => request()->string('search')->value() ?: null,
            'status' => request()->string('status')->value() ?: null,
        ];

        $userQuery = User::query()
            ->where('tenant_id', $tenant->id)
            ->with(['staff.position', 'staff.branches', 'roles'])
            ->when(
                $filters['search'] !== null,
                static fn (Builder $query): Builder => $query->where(
                    static function (Builder $userQuery) use ($filters): void {
                        $search = $filters['search'];

                        $userQuery
                            ->where('email', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('staff', static function (Builder $staffQuery) use ($search): void {
                                $staffQuery
                                    ->where('first_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('employee_number', 'like', sprintf('%%%s%%', $search));
                            });
                    },
                ),
            )
            ->when(
                $filters['status'] === 'active',
                static fn (Builder $query): Builder => $query->whereHas('staff', static fn (Builder $staffQuery): Builder => $staffQuery->where('is_active', true)),
            )
            ->when(
                $filters['status'] === 'inactive',
                static fn (Builder $query): Builder => $query->whereHas('staff', static fn (Builder $staffQuery): Builder => $staffQuery->where('is_active', false)),
            )->latest();

        $users = $userQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user): array => $this->userPayload($user, true));

        return Inertia::render('facility-manager/users', [
            'tenant' => $this->tenantSummaryPayload($tenant),
            'filters' => $filters,
            'metrics' => [
                [
                    'label' => 'Users',
                    'value' => User::query()->where('tenant_id', $tenant->id)->count(),
                    'hint' => 'Tenant-linked user accounts.',
                ],
                [
                    'label' => 'Verified',
                    'value' => $this->verifiedUsersQueryForTenant($tenant->id)->count(),
                    'hint' => 'Users with verified email addresses.',
                ],
                [
                    'label' => 'Active Staff',
                    'value' => User::query()
                        ->where('tenant_id', $tenant->id)
                        ->whereHas('staff', static fn (Builder $query): Builder => $query->where('is_active', true))
                        ->count(),
                    'hint' => 'User accounts tied to active staff members.',
                ],
            ],
            'users' => $users,
        ]);
    }

    public function subscriptions(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $tenant->loadMissing('currentSubscription.subscriptionPackage');

        $historyQuery = TenantSubscription::query()
            ->where('tenant_id', $tenant->id)
            ->with('subscriptionPackage')->latest();

        return Inertia::render('facility-manager/subscriptions', [
            'tenant' => $this->tenantSummaryPayload($tenant),
            'metrics' => [
                [
                    'label' => 'Subscription Records',
                    'value' => (clone $historyQuery)->count(),
                    'hint' => 'Total subscription transitions recorded for this tenant.',
                ],
                [
                    'label' => 'Active',
                    'value' => (clone $historyQuery)->where('status', SubscriptionStatus::ACTIVE)->count(),
                    'hint' => 'Records currently marked active.',
                ],
                [
                    'label' => 'Trial',
                    'value' => (clone $historyQuery)->where('status', SubscriptionStatus::TRIAL)->count(),
                    'hint' => 'Trial subscriptions still on record.',
                ],
                [
                    'label' => 'Past Due',
                    'value' => (clone $historyQuery)->where('status', SubscriptionStatus::PAST_DUE)->count(),
                    'hint' => 'Subscriptions flagged for billing follow-up.',
                ],
            ],
            'current_subscription' => $tenant->currentSubscription
                ? $this->subscriptionPayload($tenant->currentSubscription)
                : null,
            'subscription_history' => $historyQuery
                ->paginate(10)
                ->withQueryString()
                ->through(fn (TenantSubscription $subscription): array => $this->subscriptionPayload($subscription)),
        ]);
    }

    public function activity(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $lastSevenDays = CarbonImmutable::now()->subDays(7);
        $lastThirtyDays = CarbonImmutable::now()->subDays(30);

        $recentActivity = $this->recentActivityForTenant($tenant->id)
            ->sortByDesc(static fn (array $event): int => CarbonImmutable::parse($event['timestamp'])->getTimestamp())
            ->take(12)
            ->values()
            ->all();

        return Inertia::render('facility-manager/activity', [
            'tenant' => $this->tenantSummaryPayload($tenant),
            'metrics' => [
                [
                    'label' => 'Visits (7d)',
                    'value' => PatientVisit::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('registered_at', '>=', $lastSevenDays)
                        ->count(),
                    'hint' => 'Visits registered in the last 7 days.',
                ],
                [
                    'label' => 'Consultations (30d)',
                    'value' => Consultation::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('started_at', '>=', $lastThirtyDays)
                        ->count(),
                    'hint' => 'Consultations started in the last 30 days.',
                ],
                [
                    'label' => 'Lab Requests (30d)',
                    'value' => LabRequest::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('request_date', '>=', $lastThirtyDays)
                        ->count(),
                    'hint' => 'Laboratory requests created in the last 30 days.',
                ],
                [
                    'label' => 'Prescriptions (30d)',
                    'value' => $this->prescriptionsQueryForTenant($tenant->id)
                        ->where('prescriptions.created_at', '>=', $lastThirtyDays)
                        ->count(),
                    'hint' => 'Prescriptions recorded in the last 30 days.',
                ],
                [
                    'label' => 'Service Orders (30d)',
                    'value' => FacilityServiceOrder::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('ordered_at', '>=', $lastThirtyDays)
                        ->count(),
                    'hint' => 'Facility service orders placed in the last 30 days.',
                ],
            ],
            'recent_activity' => $recentActivity,
        ]);
    }

    public function notes(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        $notes = TenantSupportNote::query()
            ->where('tenant_id', $tenant->id)
            ->with('author.staff')
            ->orderByDesc('is_pinned')->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (TenantSupportNote $note): array => [
                'id' => $note->id,
                'title' => $note->title,
                'body' => $note->body,
                'is_pinned' => $note->is_pinned,
                'created_at' => $note->created_at?->toISOString(),
                'updated_at' => $note->updated_at?->toISOString(),
                'author' => $note->author ? [
                    'id' => $note->author->id,
                    'name' => $note->author->name,
                    'email' => $note->author->email,
                ] : null,
            ]);

        return Inertia::render('facility-manager/support-notes', [
            'tenant' => $this->tenantSummaryPayload($tenant),
            'metrics' => [
                [
                    'label' => 'Notes',
                    'value' => TenantSupportNote::query()->where('tenant_id', $tenant->id)->count(),
                    'hint' => 'Internal notes recorded for this facility.',
                ],
                [
                    'label' => 'Pinned',
                    'value' => TenantSupportNote::query()->where('tenant_id', $tenant->id)->where('is_pinned', true)->count(),
                    'hint' => 'Pinned notes that should stay visible first.',
                ],
            ],
            'notes' => $notes,
        ]);
    }

    public function storeNote(StoreTenantSupportNoteRequest $request, Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        TenantSupportNote::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $user->id,
            'title' => $validated['title'] ?: null,
            'body' => $validated['body'],
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
        ]);

        return to_route('facility-manager.facilities.notes', $tenant)
            ->with('success', 'Support note added for '.$tenant->name.'.');
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
                            ->where('name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('domain', 'like', sprintf('%%%s%%', $search));
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

    /**
     * @return array<string, mixed>
     */
    private function tenantSummaryPayload(Tenant $tenant): array
    {
        $tenant->loadMissing([
            'country',
            'address',
            'currentSubscription.subscriptionPackage',
        ]);

        return [
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
            'current_subscription' => $tenant->currentSubscription
                ? $this->subscriptionPayload($tenant->currentSubscription)
                : null,
        ];
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
            'package' => $subscription->subscriptionPackage ? [
                'name' => $subscription->subscriptionPackage->name,
                'price' => $subscription->subscriptionPackage->price,
            ] : null,
            'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
            'activated_at' => $subscription->activated_at?->toISOString(),
            'current_period_ends_at' => $subscription->current_period_ends_at?->toISOString(),
            'created_at' => $subscription->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, bool $includeBranches = false): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'position' => $user->staff?->position?->name,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'is_active' => $user->staff?->is_active ?? false,
            'employee_number' => $user->staff?->employee_number,
            'last_login_at' => $user->staff?->last_login_at?->toISOString(),
            'branches' => $includeBranches
                ? $user->staff?->branches
                    ?->map(static fn (FacilityBranch $branch): array => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'is_primary_location' => (bool) ($branch->pivot?->is_primary_location ?? false),
                    ])
                    ->values()
                    ->all() ?? []
                : [],
        ];
    }

    /**
     * @return Builder<Prescription>
     */
    private function prescriptionsQueryForTenant(string $tenantId): Builder
    {
        return Prescription::query()
            ->join('patient_visits', 'patient_visits.id', '=', 'prescriptions.visit_id')
            ->where('patient_visits.tenant_id', $tenantId);
    }

    /**
     * @return Builder<User>
     */
    private function verifiedUsersQueryForTenant(string $tenantId): Builder
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('email_verified_at');
    }

    /**
     * @return Collection<int, array<string, string|null>>
     */
    private function recentActivityForTenant(string $tenantId): Collection
    {
        $visitEvents = PatientVisit::query()
            ->where('tenant_id', $tenantId)
            ->with('patient')
            ->latest('registered_at')
            ->limit(5)
            ->get()
            ->map(static fn (PatientVisit $visit): array => [
                'type' => 'Visit',
                'title' => 'Visit registered',
                'subject' => $visit->patient?->fullname(),
                'timestamp' => $visit->registered_at?->toISOString(),
            ]);

        $consultationEvents = Consultation::query()
            ->where('tenant_id', $tenantId)
            ->with(['visit.patient'])
            ->latest('started_at')
            ->limit(5)
            ->get()
            ->map(static fn (Consultation $consultation): array => [
                'type' => 'Consultation',
                'title' => 'Consultation started',
                'subject' => $consultation->visit?->patient?->fullname(),
                'timestamp' => $consultation->started_at?->toISOString(),
            ]);

        $labEvents = LabRequest::query()
            ->where('tenant_id', $tenantId)
            ->with(['visit.patient'])
            ->withCount('items')
            ->latest('request_date')
            ->limit(5)
            ->get()
            ->map(static fn (LabRequest $request): array => [
                'type' => 'Laboratory',
                'title' => sprintf('Lab request created (%d test%s)', $request->items_count ?? 0, ($request->items_count ?? 0) === 1 ? '' : 's'),
                'subject' => $request->visit?->patient?->fullname(),
                'timestamp' => $request->request_date?->toISOString(),
            ]);

        $prescriptionEvents = Prescription::query()
            ->join('patient_visits', 'patient_visits.id', '=', 'prescriptions.visit_id')
            ->where('patient_visits.tenant_id', $tenantId)
            ->select('prescriptions.*')
            ->with(['visit.patient'])
            ->orderByDesc('prescriptions.created_at')
            ->limit(5)
            ->get()
            ->map(static fn (Prescription $prescription): array => [
                'type' => 'Pharmacy',
                'title' => 'Prescription created',
                'subject' => $prescription->visit?->patient?->fullname(),
                'timestamp' => $prescription->created_at?->toISOString(),
            ]);

        $serviceOrderEvents = FacilityServiceOrder::query()
            ->where('tenant_id', $tenantId)
            ->with(['visit.patient', 'service'])
            ->latest('ordered_at')
            ->limit(5)
            ->get()
            ->map(static fn (FacilityServiceOrder $order): array => [
                'type' => 'Service',
                'title' => $order->service ? 'Service ordered: '.$order->service->name : 'Service order created',
                'subject' => $order->visit?->patient?->fullname(),
                'timestamp' => $order->ordered_at?->toISOString(),
            ]);

        return collect([
            ...$visitEvents->all(),
            ...$consultationEvents->all(),
            ...$labEvents->all(),
            ...$prescriptionEvents->all(),
            ...$serviceOrderEvents->all(),
        ])->filter(static fn (array $event): bool => $event['timestamp'] !== null)->values();
    }
}
