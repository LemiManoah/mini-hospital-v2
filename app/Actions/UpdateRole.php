<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

final readonly class UpdateRole
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $permissions
     */
    public function handle(Role $role, array $attributes, array $permissions = []): Role
    {
        return DB::transaction(function () use ($role, $attributes, $permissions): Role {
            $role->update([
                'name' => $attributes['name'],
            ]);

            $role->syncPermissions($permissions);

            return $role;
        });
    }
}
