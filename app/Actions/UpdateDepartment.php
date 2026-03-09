<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Department;

final class UpdateDepartment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Department $department, array $data): bool
    {
        return $department->update($data);
    }
}
