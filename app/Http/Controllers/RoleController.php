<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateRole;
use App\Actions\DeleteRole;
use App\Actions\UpdateRole;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RoleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:roles.view', only: ['index']),
            new Middleware('permission:roles.create', only: ['create', 'store']),
            new Middleware('permission:roles.update', only: ['edit', 'update']),
            new Middleware('permission:roles.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $roles = Role::query()
            ->with('permissions')
            ->when(
                $search !== '',
                static fn (Builder $query) => $query->where('name', 'like', sprintf('%%%s%%', $search))
            )
            ->paginate(5)
            ->withQueryString();

        return Inertia::render('role/index', [
            'roles' => $roles,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        // Group permissions by their prefix (e.g., 'patients', 'visits')
        $permissions = Permission::all()->groupBy(fn (Permission $permission): string => explode('.', $permission->name)[0]);

        return Inertia::render('role/create', [
            'permissionGroups' => $permissions,
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRole $action): RedirectResponse
    {
        $validated = $request->validated();
        $permissions = $this->normalizePermissions($validated['permissions'] ?? []);
        unset($validated['permissions']);

        $action->handle($validated, $permissions);

        return to_route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions');

        // Group permissions by their prefix (e.g., 'patients', 'visits')
        $permissions = Permission::all()->groupBy(fn (Permission $permission): string => explode('.', $permission->name)[0]);

        return Inertia::render('role/edit', [
            'role' => $role,
            'permissionGroups' => $permissions,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RedirectResponse
    {
        $validated = $request->validated();
        $permissions = $this->normalizePermissions($validated['permissions'] ?? []);
        unset($validated['permissions']);

        $action->handle($role, $validated, $permissions);

        return to_route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(DeleteRoleRequest $request, Role $role, DeleteRole $action): RedirectResponse
    {
        $action->handle($role);

        return to_route('roles.index')->with('success', 'Role deleted successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function normalizePermissions(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            return [];
        }

        return collect($permissions)
            ->filter(static fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->values()
            ->all();
    }
}
