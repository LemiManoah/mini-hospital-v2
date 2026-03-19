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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DepartmentController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:departments.view', only: ['index']),
            new Middleware('permission:departments.create', only: ['create', 'store']),
            new Middleware('permission:departments.update', only: ['edit', 'update']),
            new Middleware('permission:departments.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $departments = Department::query()
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
        return Inertia::render('department/create');
    }

    public function store(StoreDepartmentRequest $request, CreateDepartment $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('department/edit', [
            'department' => $department,
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
