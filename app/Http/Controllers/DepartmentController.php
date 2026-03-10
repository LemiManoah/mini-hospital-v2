<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDepartment;
use App\Actions\DeleteDepartment;
use App\Actions\UpdateDepartment;
use App\Http\Requests\DeleteDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DepartmentController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $departments = Department::query()
            ->with(['headOfDepartment'])
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('department_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('department_code', 'like', sprintf('%%%s%%', $search))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('department/index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        $staff = Staff::query()->forActiveBranch()->select('id', 'first_name', 'last_name')->get()->map(fn ($s): array => [
            'id' => $s->id,
            'name' => $s->first_name.' '.$s->last_name,
        ]);

        return Inertia::render('department/create', [
            'staff' => $staff,
        ]);
    }

    public function store(StoreDepartmentRequest $request, CreateDepartment $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): Response
    {
        $staff = Staff::query()->forActiveBranch()->select('id', 'first_name', 'last_name')->get()->map(fn ($s): array => [
            'id' => $s->id,
            'name' => $s->first_name.' '.$s->last_name,
        ]);

        return Inertia::render('department/edit', [
            'department' => $department,
            'staff' => $staff,
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department, UpdateDepartment $action): RedirectResponse
    {
        $action->handle($department, $request->validated());

        return to_route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(DeleteDepartmentRequest $request, Department $department, DeleteDepartment $action): RedirectResponse
    {
        $action->handle($department);

        return to_route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
