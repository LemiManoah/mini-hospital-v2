<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateRole;
use App\Actions\DeleteRole;
use App\Actions\UpdateRole;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RoleController
{
    public function index(): Response
    {
        $roles = Role::with('permissions')->get();
        return Inertia::render('role/index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        // Group permissions by their prefix (e.g., 'patients', 'visits')
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return Inertia::render('role/create', [
            'permissionGroups' => $permissions,
        ]);
    }

    public function store(StoreRoleRequest $request, CreateRole $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->safe()->except('permissions');
        $permissions = $request->input('permissions', []);

        $action->handle($attributes, $permissions);

        return to_route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions');
        
        // Group permissions by their prefix (e.g., 'patients', 'visits')
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return Inertia::render('role/edit', [
            'role' => $role,
            'permissionGroups' => $permissions,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->safe()->except('permissions');
        $permissions = $request->input('permissions', []);

        $action->handle($role, $attributes, $permissions);

        return to_route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role, DeleteRole $action): RedirectResponse
    {
        $action->handle($role);

        return to_route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
