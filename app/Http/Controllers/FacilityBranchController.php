<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateFacilityBranch;
use App\Actions\DeleteFacilityBranch;
use App\Actions\UpdateFacilityBranch;
use App\Http\Requests\DeleteFacilityBranchRequest;
use App\Http\Requests\StoreFacilityBranchRequest;
use App\Http\Requests\UpdateFacilityBranchRequest;
use App\Models\Clinic;
use App\Models\Currency;
use App\Models\FacilityBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilityBranchController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:facility_branches.view', only: ['index']),
            new Middleware('permission:facility_branches.create', only: ['create', 'store']),
            new Middleware('permission:facility_branches.update', only: ['edit', 'update']),
            new Middleware('permission:facility_branches.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FacilityBranch::class);

        $search = mb_trim((string) $request->query('search', ''));

        $branches = FacilityBranch::query()
            ->with('currency:id,code,name,symbol')
            ->withCount(['staff'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query
                    ->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('branch_code', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $search))
            )
            ->orderByDesc('is_main_branch')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('facility-branch/index', [
            'branches' => $branches,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', FacilityBranch::class);

        return Inertia::render('facility-branch/create', [
            'currencies' => Currency::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'symbol']),
        ]);
    }

    public function store(StoreFacilityBranchRequest $request, CreateFacilityBranch $action): RedirectResponse
    {
        Gate::authorize('create', FacilityBranch::class);

        $action->handle($request->validated());

        return to_route('facility-branches.index')->with('success', 'Facility branch created successfully.');
    }

    public function edit(FacilityBranch $facilityBranch): Response
    {
        Gate::authorize('update', $facilityBranch);

        return Inertia::render('facility-branch/edit', [
            'branch' => $facilityBranch->load('currency:id,code,name,symbol'),
            'currencies' => Currency::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'symbol']),
        ]);
    }

    public function update(
        UpdateFacilityBranchRequest $request,
        FacilityBranch $facilityBranch,
        UpdateFacilityBranch $action,
    ): RedirectResponse {
        Gate::authorize('update', $facilityBranch);

        $action->handle($facilityBranch, $request->validated());

        return to_route('facility-branches.index')->with('success', 'Facility branch updated successfully.');
    }

    public function destroy(
        DeleteFacilityBranchRequest $request,
        FacilityBranch $facilityBranch,
        DeleteFacilityBranch $action,
    ): RedirectResponse {
        Gate::authorize('delete', $facilityBranch);

        if ($facilityBranch->is_main_branch) {
            return to_route('facility-branches.index')
                ->with('error', 'The main branch cannot be deleted.');
        }

        if ($facilityBranch->staff()->exists()) {
            return to_route('facility-branches.index')
                ->with('error', 'This branch still has staff assigned to it.');
        }

        if (Clinic::query()->where('branch_id', $facilityBranch->id)->exists()) {
            return to_route('facility-branches.index')
                ->with('error', 'This branch still has clinics attached to it.');
        }

        $action->handle($facilityBranch);

        return to_route('facility-branches.index')->with('success', 'Facility branch deleted successfully.');
    }
}
