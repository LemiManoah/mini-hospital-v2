<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStaff;
use App\Actions\DeleteStaff;
use App\Actions\UpdateStaff;
use App\Http\Requests\DeleteStaffRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StaffController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:staff.view', only: ['index']),
            new Middleware('permission:staff.create', only: ['create', 'store']),
            new Middleware('permission:staff.update', only: ['edit', 'update']),
            new Middleware('permission:staff.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $staff = Staff::query()
            ->forActiveBranch()
            ->with(['departments', 'position', 'branches'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query
                    ->where('first_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('email', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('staff/index', [
            'staff' => $staff,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $departments = Department::query()->select('id', 'department_name')->get();
        $positions = StaffPosition::query()->select('id', 'name')->get();
        $branches = BranchContext::getAccessibleBranches($user)
            ->where('id', BranchContext::getActiveBranchId())
            ->map(fn (FacilityBranch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
            ]);

        return Inertia::render('staff/create', [
            'departments' => $departments,
            'positions' => $positions,
            'branches' => $branches,
        ]);
    }

    public function store(StoreStaffRequest $request, CreateStaff $action): RedirectResponse
    {
        $validated = $request->validated();

        if (! $this->usesActiveBranchAssignments($validated)) {
            return back()->with('error', 'Staff can only be assigned to the active branch from this workspace.');
        }

        $action->handle($validated);

        return to_route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $this->authorizeStaff($staff);

        $staff->load(['departments', 'position', 'branches']);
        $departments = Department::query()->select('id', 'department_name')->get();
        $positions = StaffPosition::query()->select('id', 'name')->get();
        $branches = BranchContext::getAccessibleBranches($user)
            ->where('id', BranchContext::getActiveBranchId())
            ->map(fn (FacilityBranch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
            ]);

        return Inertia::render('staff/edit', [
            'staff' => $staff,
            'departments' => $departments,
            'positions' => $positions,
            'branches' => $branches,
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff, UpdateStaff $action): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $validated = $request->validated();

        if (! $this->usesActiveBranchAssignments($validated)) {
            return back()->with('error', 'Staff can only be assigned to the active branch from this workspace.');
        }

        $action->handle($staff, $validated);

        return to_route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(DeleteStaffRequest $request, Staff $staff, DeleteStaff $action): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $action->handle($staff);

        return to_route('staff.index')->with('success', 'Staff member deleted successfully.');
    }

    private function authorizeStaff(Staff $staff): void
    {
        abort_unless(
            $staff->branches()
                ->where('facility_branches.id', BranchContext::getActiveBranchId())
                ->exists(),
            403,
            'You do not have access to this staff record in the active branch.',
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function usesActiveBranchAssignments(array $attributes): bool
    {
        $activeBranchId = BranchContext::getActiveBranchId();
        $branchIds = $attributes['branch_ids'] ?? [];
        $primaryBranchId = $attributes['primary_branch_id'] ?? null;

        return is_string($activeBranchId)
            && $activeBranchId !== ''
            && is_array($branchIds)
            && $branchIds !== []
            && count(array_unique($branchIds)) === 1
            && $branchIds[0] === $activeBranchId
            && $primaryBranchId === $activeBranchId;
    }
}
