<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Department;

final class DeleteDepartment
{
    public function handle(Department $department): ?bool
    {
        return $department->delete();
    }
}
