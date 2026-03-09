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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StaffController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $staff = Staff::query()
            ->with(['department', 'position', 'branches'])
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
        $departments = Department::query()->select('id', 'department_name')->get();
        $positions = StaffPosition::query()->select('id', 'name')->get();
        $branches = FacilityBranch::query()->select('id', 'name')->get();

        return Inertia::render('staff/create', [
            'departments' => $departments,
            'positions' => $positions,
            'branches' => $branches,
        ]);
    }

    public function store(StoreStaffRequest $request, CreateStaff $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): Response
    {
        $staff->load(['department', 'position', 'branches']);
        $departments = Department::query()->select('id', 'department_name')->get();
        $positions = StaffPosition::query()->select('id', 'name')->get();
        $branches = FacilityBranch::query()->select('id', 'name')->get();

        return Inertia::render('staff/edit', [
            'staff' => $staff,
            'departments' => $departments,
            'positions' => $positions,
            'branches' => $branches,
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff, UpdateStaff $action): RedirectResponse
    {
        $action->handle($staff, $request->validated());

        return to_route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(DeleteStaffRequest $request, Staff $staff, DeleteStaff $action): RedirectResponse
    {
        $action->handle($staff);

        return to_route('staff.index')->with('success', 'Staff member deleted successfully.');
    }
}
