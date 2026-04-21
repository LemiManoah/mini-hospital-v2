<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\ImpersonationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilityImpersonationController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tenants.impersonate', only: [
                'index',
                'start',
            ]),
        ];
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->value() ?: null,
            'facility_id' => $request->string('facility_id')->value() ?: null,
            'role' => $request->string('role')->value() ?: null,
        ];

        $users = User::query()
            ->whereNotNull('tenant_id')
            ->where('is_support', false)
            ->with([
                'tenant:id,name',
                'staff.position:id,name',
                'staff.branches:id,name',
                'roles:id,name',
            ])
            ->when(
                $filters['search'] !== null,
                static function (Builder $query) use ($filters): void {
                    $search = $filters['search'];

                    $query->where(static function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('email', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('staff', static function (Builder $staffQuery) use ($search): void {
                                $staffQuery
                                    ->where('first_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('employee_number', 'like', sprintf('%%%s%%', $search));
                            })
                            ->orWhereHas('tenant', static function (Builder $tenantQuery) use ($search): void {
                                $tenantQuery->where('name', 'like', sprintf('%%%s%%', $search));
                            });
                    });
                },
            )
            ->when(
                $filters['facility_id'] !== null,
                static fn (Builder $query): Builder => $query->where('tenant_id', $filters['facility_id']),
            )
            ->when(
                $filters['role'] !== null,
                static fn (Builder $query): Builder => $query->whereHas(
                    'roles',
                    static fn (Builder $roleQuery): Builder => $roleQuery->where('name', $filters['role']),
                ),
            )
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->staff?->position?->name,
                'employee_number' => $user->staff?->employee_number,
                'is_active' => $user->staff?->is_active ?? false,
                'last_login_at' => $user->staff?->last_login_at?->toISOString(),
                'tenant' => $user->tenant ? [
                    'id' => $user->tenant->id,
                    'name' => $user->tenant->name,
                ] : null,
                'roles' => $user->roles
                    ->pluck('name')
                    ->values()
                    ->all(),
                'branches' => $user->staff?->branches
                    ?->map(static fn ($branch): array => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                    ])
                    ->values()
                    ->all() ?? [],
            ]);

        return Inertia::render('facility-manager/impersonation/index', [
            'filters' => $filters,
            'facility_options' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (Tenant $tenant): array => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                ])
                ->all(),
            'role_options' => Role::query()
                ->orderBy('name')
                ->get(['name'])
                ->map(static fn (Role $role): array => [
                    'name' => $role->name,
                ])
                ->all(),
            'users' => $users,
        ]);
    }

    public function start(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403, 'Unauthorized.');
        abort_if($user->isSupportUser(), 403, 'Support users cannot be impersonated.');
        abort_if($user->tenantId() === null, 404, 'This user is not linked to a facility.');

        $user->loadMissing('tenant');

        ImpersonationContext::start($request, $actor, $user);
        BranchContext::clear();
        $request->session()->regenerate();

        if ($user->tenant !== null && ! $user->tenant->isOnboardingComplete()) {
            return to_route('onboarding.show')
                ->with('success', 'Now acting as '.$user->name.'.');
        }

        $accessibleBranches = BranchContext::getAccessibleBranches($user);

        if ($accessibleBranches->isEmpty()) {
            BranchContext::clear();

            return to_route('dashboard')
                ->with('success', 'Now acting as '.$user->name.'.');
        }

        BranchContext::setActiveBranchId((string) $accessibleBranches->first()->id);

        if ($accessibleBranches->count() > 1) {
            return to_route('branch-switcher.index')
                ->with('success', 'Now acting as '.$user->name.'.');
        }

        return to_route('dashboard')
            ->with('success', 'Now acting as '.$user->name.'.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $realUser = ImpersonationContext::realUser($request);

        abort_unless($realUser instanceof User, 403, 'No active impersonation session was found.');
        abort_if(! $realUser->isSupportUser() && ! $realUser->hasRole('super_admin'), 403, 'Only support users can stop impersonation.');

        ImpersonationContext::stop($request);
        BranchContext::clear();
        $request->session()->regenerate();

        return to_route('facility-manager.impersonation.index')
            ->with('success', 'Returned to your support account.');
    }
}
