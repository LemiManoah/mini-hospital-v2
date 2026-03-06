<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;
use Illuminate\Validation\ValidationException;

final readonly class DeleteRole
{
    public function handle(Role $role): void
    {
        if (in_array($role->name, ['super_admin'])) {
            throw ValidationException::withMessages([
                'role' => 'The super admin role cannot be deleted.',
            ]);
        }

        $role->delete();
    }
}
