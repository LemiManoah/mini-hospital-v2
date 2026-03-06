<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

final readonly class CreateRole
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $permissions
     */
    public function handle(array $attributes, array $permissions = []): Role
    {
        return DB::transaction(function () use ($attributes, $permissions): Role {
            $role = Role::query()->create([
                'name' => $attributes['name'],
                'guard_name' => 'web',
            ]);

            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
            }

            return $role;
        });
    }
}
