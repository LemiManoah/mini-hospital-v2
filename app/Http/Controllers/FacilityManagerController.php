<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterWorkspace;
use App\Actions\StartTenantSubscription;
use App\Actions\UpdateTenantSupportWorkflow;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantSupportPriority;
use App\Enums\TenantSupportStatus;
use App\Http\Requests\StoreTenantSupportNoteRequest;
use App\Http\Requests\StoreWorkspaceRegistrationRequest;
use App\Http\Requests\UpdateTenantSupportWorkflowRequest;
use App\Models\Consultation;
use App\Models\Country;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\FacilityService;
use App\Models\FacilityServiceOrder;
use App\Models\InventoryLocation;
use App\Models\LabRequest;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\Staff;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantSupportNote;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class FacilityManagerController implements HasMiddleware
{
    public function __construct(
        private RegisterWorkspace $registerWorkspace,
        private StartTenantSubscription $startTenantSubscription,
        private UpdateTenantSupportWorkflow $updateTenantSupportWorkflow,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:tenants.view', only: [
                'dashboard',
                'index',
                'create',
                'store',
                'export',
                'audit',
                'show',
                'branches',
                'users',
                'subscriptions',
                'activity',
                'notes',
            ]),
            new Middleware('permission:tenants.update', only: [
                'create',
                'store',
                'storeNote',
                'updateSupportWorkflow',
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
            ->filter(fn (Tenant $tenant): bool => ! $tenant->isOnboardingComplete()
                || $this->subscriptionStatusValueOrNull($tenant->currentSubscription) === SubscriptionStatus::PAST_DUE->value
                || $tenant->currentSubscription === null
                || $this->tenantSupportStatus($tenant)->needsAttention())
            ->sortBy(fn (Tenant $tenant): string => sprintf(
                '%d-%d-%d-%s',
                $tenant->isOnboardingComplete() ? 1 : 0,
                $this->subscriptionStatusValueOrNull($tenant->currentSubscription) === SubscriptionStatus::PAST_DUE->value ? 0 : 1,
                $this->tenantSupportStatus($tenant)->needsAttention() ? 0 : 1,
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
            'support' => $request->string('support')->value() ?: null,
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

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Tenant::class);

        $query = $this->tenantsWithCounts($this->filteredTenantQuery($request))
            ->orderBy('name');

        $filename = sprintf('facility-manager-facilities-%s.csv', now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            throw_if($handle === false, RuntimeException::class, 'Unable to open output stream for facility export.');

            fputcsv($handle, [
                'Facility',
                'Domain',
                'Facility Level',
                'Onboarding',
                'Subscription',
                'Package',
                'Support Status',
                'Support Priority',
                'Next Follow-Up',
                'Last Contacted',
                'Branches',
                'Users',
                'Patients',
                'Visits',
                'Lab Requests',
                'Prescriptions',
            ], escape: '\\');

            $query->each(function (Tenant $tenant) use ($handle): void {
                /** @var array<int, bool|float|int|string|null> $row */
                $row = [
                    $tenant->name,
                    $tenant->domain,
                    $tenant->facility_level->value,
                    $tenant->isOnboardingComplete() ? 'Completed' : 'Open',
                    $tenant->currentSubscription !== null ? $this->subscriptionStatusLabel($tenant->currentSubscription) : 'No subscription',
                    $tenant->currentSubscription !== null
                        ? ($tenant->currentSubscription->subscriptionPackage !== null
                            ? $tenant->currentSubscription->subscriptionPackage->name
                            : '')
                        : '',
                    $this->tenantSupportStatus($tenant)->label(),
                    $this->tenantSupportPriority($tenant)->label(),
                    $tenant->support_follow_up_at?->format('Y-m-d H:i') ?? '',
                    $tenant->support_last_contacted_at?->format('Y-m-d H:i') ?? '',
                    $tenant->branches_count ?? 0,
                    $tenant->users_count ?? 0,
                    $tenant->patients_count ?? 0,
                    $tenant->visits_count ?? 0,
                    $tenant->lab_requests_count ?? 0,
                    $tenant->prescriptions_count ?? 0,
                ];

                fputcsv($handle, $row, escape: '\\');
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function create(): Response
    {
        return Inertia::render('facility-manager/create', [
            'facilityLevels' => $this->facilityLevelOptions(),
            'subscriptionPackages' => $this->subscriptionPackageOptions(),
            'countries' => $this->countryOptions(),
        ]);
    }

    public function store(StoreWorkspaceRegistrationRequest $request): RedirectResponse
    {
        $workspace = $this->registerWorkspace->handle($request->createDto(), $request->password());

        return to_route('facility-manager.facilities.show', $workspace['tenant'])
            ->with('success', 'Facility created successfully. Use impersonation to continue onboarding when needed.');
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
                        'status' => $branch->status->value,
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
            'health' => $this->facilityHealthPayload($tenant),
        ]);
    }

    public function audit(Tenant $tenant): Response
    {
        Gate::authorize('view', $tenant);

        return Inertia::render('facility-manager/audit', [
            'tenant' => $this->tenantSummaryPayload($tenant),
            'health' => $this->facilityHealthPayload($tenant),
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
                    'value' => $branches->filter(fn (FacilityBranch $branch): bool => $branch->status->value === 'active')->count(),
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
                    'status' => $branch->status->value,
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

    public function updateSupportWorkflow(UpdateTenantSupportWorkflowRequest $request, Tenant $tenant): RedirectResponse
    {
        Gate::authorize('update', $tenant);

        $validated = $request->validated();
        $status = $validated['status'] ?? null;
        $priority = $validated['priority'] ?? null;

        abort_if(! is_string($status) || ! is_string($priority), 422, 'Support workflow status and priority must be strings.');

        $this->updateTenantSupportWorkflow->handle($tenant, [
            'status' => $status,
            'priority' => $priority,
            'follow_up_at' => $validated['follow_up_at'] ?? null,
            'last_contacted_at' => $validated['last_contacted_at'] ?? null,
        ]);

        return to_route('facility-manager.facilities.notes', $tenant)
            ->with('success', 'Support workflow updated for '.$tenant->name.'.');
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

    private static function isPrimaryBranchLocation(FacilityBranch $branch): bool
    {
        $pivot = $branch->getRelations()['pivot'] ?? null;

        return $pivot instanceof Model
            && (bool) $pivot->getAttribute('is_primary_location');
    }

    /**
     * @return Builder<Tenant>
     */
    private function filteredTenantQuery(Request $request): Builder
    {
        $search = $request->string('search')->value();
        $onboarding = $request->string('onboarding')->value();
        $subscription = $request->string('subscription')->value();
        $support = $request->string('support')->value();

        return Tenant::query()
            ->with('currentSubscription.subscriptionPackage')
            ->when(
                $search !== '',
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
                $subscription !== '' && $subscription !== 'no_subscription',
                static fn (Builder $query): Builder => $query->whereHas(
                    'currentSubscription',
                    static fn (Builder $subscriptionQuery): Builder => $subscriptionQuery->where('status', $subscription),
                ),
            )
            ->when(
                $support !== '',
                static fn (Builder $query): Builder => $query->where('support_status', $support),
            );
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
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
            'status' => $tenant->status->value,
            'facility_level' => $tenant->facility_level->value,
            'onboarding_completed_at' => $tenant->onboarding_completed_at?->toISOString(),
            'support_workflow' => $this->tenantSupportWorkflowPayload($tenant),
            'current_subscription' => $tenant->currentSubscription ? [
                'status' => $this->subscriptionStatusValue($tenant->currentSubscription),
                'status_label' => $this->subscriptionStatusLabel($tenant->currentSubscription),
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
            'status' => $tenant->status->value,
            'facility_level' => $tenant->facility_level->value,
            'onboarding_completed_at' => $tenant->onboarding_completed_at?->toISOString(),
            'support_workflow' => $this->tenantSupportWorkflowPayload($tenant),
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
            'status' => $this->subscriptionStatusValue($subscription),
            'status_label' => $this->subscriptionStatusLabel($subscription),
            'package' => $subscription->subscriptionPackage ? [
                'name' => $subscription->subscriptionPackage->name,
                'price' => $subscription->subscriptionPackage->price,
            ] : null,
            'trial_ends_at' => $subscription->trial_ends_at,
            'activated_at' => $subscription->activated_at,
            'current_period_ends_at' => $subscription->current_period_ends_at,
            'created_at' => $subscription->created_at?->toISOString(),
        ];
    }

    /**
     * @return array{status: string, status_label: string, priority: string, priority_label: string, follow_up_at: string|null, last_contacted_at: string|null}
     */
    private function tenantSupportWorkflowPayload(Tenant $tenant): array
    {
        return [
            'status' => $this->tenantSupportStatus($tenant)->value,
            'status_label' => $this->tenantSupportStatus($tenant)->label(),
            'priority' => $this->tenantSupportPriority($tenant)->value,
            'priority_label' => $this->tenantSupportPriority($tenant)->label(),
            'follow_up_at' => $this->tenantSupportDateTimeValue($tenant, 'support_follow_up_at'),
            'last_contacted_at' => $this->tenantSupportDateTimeValue($tenant, 'support_last_contacted_at'),
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
            'created_at' => $user->created_at->toISOString(),
            'is_active' => $user->staff !== null ? $user->staff->is_active : false,
            'employee_number' => $user->staff?->employee_number,
            'last_login_at' => $user->staff?->last_login_at?->toISOString(),
            'branches' => $includeBranches
                ? $user->staff?->branches
                    ?->map(static fn (FacilityBranch $branch): array => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'is_primary_location' => self::isPrimaryBranchLocation($branch),
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
     * @return Collection<int, array{type: string, title: string, subject: string|null, timestamp: string}>
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
                'timestamp' => $consultation->started_at->toISOString(),
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
                'timestamp' => $prescription->created_at->toISOString(),
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

        /** @var Collection<int, array{type: string, title: string, subject: string|null, timestamp: string}> $events */
        $events = collect([
            ...$visitEvents->all(),
            ...$consultationEvents->all(),
            ...$labEvents->all(),
            ...$prescriptionEvents->all(),
            ...$serviceOrderEvents->all(),
        ])->filter(static fn (array $event): bool => $event['timestamp'] !== null)->values();

        return $events;
    }

    private function subscriptionStatusValue(TenantSubscription $subscription): string
    {
        return $this->subscriptionStatus($subscription)->value;
    }

    private function subscriptionStatusLabel(TenantSubscription $subscription): string
    {
        return $this->subscriptionStatus($subscription)->label();
    }

    private function subscriptionStatus(TenantSubscription $subscription): SubscriptionStatus
    {
        $status = $subscription->getAttributeValue('status');

        if ($status instanceof SubscriptionStatus) {
            return $status;
        }

        if (is_string($status)) {
            return SubscriptionStatus::from($status);
        }

        return SubscriptionStatus::PENDING_ACTIVATION;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function facilityLevelOptions(): array
    {
        return collect(FacilityLevel::cases())
            ->map(static fn (FacilityLevel $level): array => [
                'value' => $level->value,
                'label' => $level->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string, users: int, price: mixed}>
     */
    private function subscriptionPackageOptions(): array
    {
        return SubscriptionPackage::query()
            ->orderBy('users')
            ->orderBy('name')
            ->get(['id', 'name', 'users', 'price'])
            ->map(static fn (SubscriptionPackage $package): array => [
                'id' => (string) $package->id,
                'name' => $package->name,
                'users' => (int) $package->users,
                'price' => $package->price,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function countryOptions(): array
    {
        return Country::query()
            ->orderBy('country_name')
            ->get(['id', 'country_name'])
            ->map(static fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->country_name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     status: string,
     *     status_label: string,
     *     summary: array{total_checks: int, passed: int, warnings: int, critical: int},
     *     checks: array<int, array{
     *         key: string,
     *         label: string,
     *         status: string,
     *         status_label: string,
     *         detail: string,
     *         recommendation: string
     *     }>,
     *     recommendations: array<int, string>
     * }
     */
    private function facilityHealthPayload(Tenant $tenant): array
    {
        $tenant->loadMissing('currentSubscription.subscriptionPackage');

        $thirtyDaysAgo = CarbonImmutable::now()->subDays(30);
        $activeBranches = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', GeneralStatus::ACTIVE)
            ->count();
        $hasPrimaryBranch = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_main_branch', true)
            ->exists();
        $departmentCount = Department::query()
            ->where('tenant_id', $tenant->id)
            ->count();
        $userCount = User::query()
            ->where('tenant_id', $tenant->id)
            ->count();
        $verifiedUsers = $this->verifiedUsersQueryForTenant($tenant->id)->count();
        $activeStaff = Staff::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();
        $serviceCatalogItems = FacilityService::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();
        $labCatalogItems = LabTestCatalog::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();
        $inventoryLocations = InventoryLocation::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();
        $recentOperationalActivity = PatientVisit::query()
            ->where('tenant_id', $tenant->id)
            ->where('registered_at', '>=', $thirtyDaysAgo)
            ->count()
            + LabRequest::query()
                ->where('tenant_id', $tenant->id)
                ->where('request_date', '>=', $thirtyDaysAgo)
                ->count()
            + $this->prescriptionsQueryForTenant($tenant->id)
                ->where('prescriptions.created_at', '>=', $thirtyDaysAgo)
                ->count()
            + FacilityServiceOrder::query()
                ->where('tenant_id', $tenant->id)
                ->where('ordered_at', '>=', $thirtyDaysAgo)
                ->count();

        $subscriptionStatus = $tenant->currentSubscription instanceof TenantSubscription
            ? $this->subscriptionStatus($tenant->currentSubscription)
            : null;

        /** @var array<int, array{
         *     key: string,
         *     label: string,
         *     status: string,
         *     status_label: string,
         *     detail: string,
         *     recommendation: string
         * }> $checks
         */
        $checks = [
            $this->healthCheck(
                key: 'onboarding',
                label: 'Onboarding Completion',
                status: $tenant->isOnboardingComplete() ? 'pass' : 'warning',
                detail: $tenant->isOnboardingComplete()
                    ? 'Core onboarding has been completed for this facility.'
                    : 'This facility is still in the onboarding flow.',
                recommendation: $tenant->isOnboardingComplete()
                    ? 'No action needed.'
                    : 'Impersonate the facility user and complete the remaining onboarding steps.',
            ),
            $this->healthCheck(
                key: 'subscription',
                label: 'Subscription State',
                status: match ($subscriptionStatus) {
                    SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL => 'pass',
                    SubscriptionStatus::PENDING_ACTIVATION, SubscriptionStatus::PAST_DUE => 'warning',
                    SubscriptionStatus::CANCELLED, null => 'critical',
                },
                detail: match ($subscriptionStatus) {
                    SubscriptionStatus::ACTIVE => 'An active subscription is in place.',
                    SubscriptionStatus::TRIAL => 'The facility is operating on a trial subscription.',
                    SubscriptionStatus::PENDING_ACTIVATION => 'A subscription exists but still needs activation follow-up.',
                    SubscriptionStatus::PAST_DUE => 'The subscription is past due and needs billing intervention.',
                    SubscriptionStatus::CANCELLED => 'The current subscription has been cancelled.',
                    null => 'No subscription record exists for this facility.',
                },
                recommendation: match ($subscriptionStatus) {
                    SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL => 'No action needed.',
                    SubscriptionStatus::PENDING_ACTIVATION => 'Complete the activation handoff and confirm billing status.',
                    SubscriptionStatus::PAST_DUE => 'Follow up on billing and either reactivate or confirm the facility status.',
                    SubscriptionStatus::CANCELLED, null => 'Create or restore a valid subscription record before go-live support.',
                },
            ),
            $this->healthCheck(
                key: 'primary_branch',
                label: 'Primary Branch',
                status: $hasPrimaryBranch ? 'pass' : 'critical',
                detail: $hasPrimaryBranch
                    ? 'A primary branch is configured for the facility.'
                    : 'No primary branch has been configured yet.',
                recommendation: $hasPrimaryBranch
                    ? 'No action needed.'
                    : 'Resume onboarding or configure the first operating branch.',
            ),
            $this->healthCheck(
                key: 'active_branches',
                label: 'Active Branches',
                status: $activeBranches > 0 ? 'pass' : 'critical',
                detail: $activeBranches > 0
                    ? sprintf('%d active branch%s available.', $activeBranches, $activeBranches === 1 ? '' : 'es')
                    : 'No active branches are available.',
                recommendation: $activeBranches > 0
                    ? 'No action needed.'
                    : 'Review branch setup and reactivate or create a working branch.',
            ),
            $this->healthCheck(
                key: 'departments',
                label: 'Departments',
                status: $departmentCount > 0 ? 'pass' : 'warning',
                detail: $departmentCount > 0
                    ? sprintf('%d department%s configured.', $departmentCount, $departmentCount === 1 ? '' : 's')
                    : 'No departments have been configured.',
                recommendation: $departmentCount > 0
                    ? 'No action needed.'
                    : 'Add operational departments so staff can be assigned correctly.',
            ),
            $this->healthCheck(
                key: 'users',
                label: 'User Access',
                status: match (true) {
                    $userCount === 0 => 'critical',
                    $verifiedUsers === 0 => 'warning',
                    default => 'pass',
                },
                detail: match (true) {
                    $userCount === 0 => 'No tenant-linked user accounts exist.',
                    $verifiedUsers === 0 => 'User accounts exist, but none are verified yet.',
                    default => sprintf('%d verified user%s ready for access.', $verifiedUsers, $verifiedUsers === 1 ? '' : 's'),
                },
                recommendation: match (true) {
                    $userCount === 0 => 'Create or restore at least one tenant user account.',
                    $verifiedUsers === 0 => 'Have the facility owner verify email access or reset access as support.',
                    default => 'No action needed.',
                },
            ),
            $this->healthCheck(
                key: 'active_staff',
                label: 'Active Staff Records',
                status: $activeStaff > 0 ? 'pass' : 'warning',
                detail: $activeStaff > 0
                    ? sprintf('%d active staff record%s configured.', $activeStaff, $activeStaff === 1 ? '' : 's')
                    : 'No active staff records are configured.',
                recommendation: $activeStaff > 0
                    ? 'No action needed.'
                    : 'Complete the first staff setup so operational users can work in the tenant.',
            ),
            $this->healthCheck(
                key: 'facility_services',
                label: 'Facility Services Catalog',
                status: $serviceCatalogItems > 0 ? 'pass' : 'warning',
                detail: $serviceCatalogItems > 0
                    ? sprintf('%d active facility service%s configured.', $serviceCatalogItems, $serviceCatalogItems === 1 ? '' : 's')
                    : 'No active facility services are configured.',
                recommendation: $serviceCatalogItems > 0
                    ? 'No action needed.'
                    : 'Add facility services before procedures and service orders go live.',
            ),
            $this->healthCheck(
                key: 'lab_catalog',
                label: 'Laboratory Catalog',
                status: $labCatalogItems > 0 ? 'pass' : 'warning',
                detail: $labCatalogItems > 0
                    ? sprintf('%d active lab test%s configured.', $labCatalogItems, $labCatalogItems === 1 ? '' : 's')
                    : 'No active lab tests are configured.',
                recommendation: $labCatalogItems > 0
                    ? 'No action needed.'
                    : 'Load the lab catalog before laboratory ordering begins.',
            ),
            $this->healthCheck(
                key: 'inventory_locations',
                label: 'Inventory Locations',
                status: $inventoryLocations > 0 ? 'pass' : 'warning',
                detail: $inventoryLocations > 0
                    ? sprintf('%d active inventory location%s configured.', $inventoryLocations, $inventoryLocations === 1 ? '' : 's')
                    : 'No active inventory locations are configured.',
                recommendation: $inventoryLocations > 0
                    ? 'No action needed.'
                    : 'Create inventory locations before stock, pharmacy, or lab store workflows begin.',
            ),
            $this->healthCheck(
                key: 'recent_activity',
                label: 'Recent Operational Activity',
                status: $recentOperationalActivity > 0 ? 'pass' : 'warning',
                detail: $recentOperationalActivity > 0
                    ? sprintf('%d recent operational event%s recorded in the last 30 days.', $recentOperationalActivity, $recentOperationalActivity === 1 ? '' : 's')
                    : 'No visits, lab requests, prescriptions, or service orders were recorded in the last 30 days.',
                recommendation: $recentOperationalActivity > 0
                    ? 'No action needed.'
                    : 'Confirm whether the facility is newly onboarded, inactive, or blocked by missing setup.',
            ),
        ];

        $critical = collect($checks)->where('status', 'critical')->count();
        $warnings = collect($checks)->where('status', 'warning')->count();
        $passed = collect($checks)->where('status', 'pass')->count();
        $overallStatus = $critical > 0 ? 'critical' : ($warnings > 0 ? 'warning' : 'healthy');

        return [
            'status' => $overallStatus,
            'status_label' => match ($overallStatus) {
                'critical' => 'Critical Attention',
                'warning' => 'Needs Follow-Up',
                default => 'Healthy',
            },
            'summary' => [
                'total_checks' => count($checks),
                'passed' => $passed,
                'warnings' => $warnings,
                'critical' => $critical,
            ],
            'checks' => $checks,
            'recommendations' => collect($checks)
                ->where('status', '!=', 'pass')
                ->map(static fn (array $check): string => $check['recommendation'])
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     status: string,
     *     status_label: string,
     *     detail: string,
     *     recommendation: string
     * }
     */
    private function healthCheck(
        string $key,
        string $label,
        string $status,
        string $detail,
        string $recommendation,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'status_label' => match ($status) {
                'critical' => 'Critical',
                'warning' => 'Warning',
                default => 'Passed',
            },
            'detail' => $detail,
            'recommendation' => $recommendation,
        ];
    }

    private function subscriptionStatusValueOrNull(?TenantSubscription $subscription): ?string
    {
        return $subscription instanceof TenantSubscription
            ? $this->subscriptionStatusValue($subscription)
            : null;
    }

    private function tenantSupportStatus(Tenant $tenant): TenantSupportStatus
    {
        $attributes = $tenant->getAttributes();

        if (! array_key_exists('support_status', $attributes)) {
            return TenantSupportStatus::STABLE;
        }

        $status = $tenant->getAttributeValue('support_status');

        if ($status instanceof TenantSupportStatus) {
            return $status;
        }

        return is_string($status)
            ? TenantSupportStatus::tryFrom($status) ?? TenantSupportStatus::STABLE
            : TenantSupportStatus::STABLE;
    }

    private function tenantSupportPriority(Tenant $tenant): TenantSupportPriority
    {
        $attributes = $tenant->getAttributes();

        if (! array_key_exists('support_priority', $attributes)) {
            return TenantSupportPriority::NORMAL;
        }

        $priority = $tenant->getAttributeValue('support_priority');

        if ($priority instanceof TenantSupportPriority) {
            return $priority;
        }

        return is_string($priority)
            ? TenantSupportPriority::tryFrom($priority) ?? TenantSupportPriority::NORMAL
            : TenantSupportPriority::NORMAL;
    }

    private function tenantSupportDateTimeValue(Tenant $tenant, string $attribute): ?string
    {
        $attributes = $tenant->getAttributes();

        if (! array_key_exists($attribute, $attributes)) {
            return null;
        }

        $value = $tenant->getAttributeValue($attribute);

        if (! $value instanceof DateTimeInterface) {
            return null;
        }

        return $value->format(DATE_ATOM);
    }
}
