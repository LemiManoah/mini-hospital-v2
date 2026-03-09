<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Department;

final class CreateDepartment
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Department
    {
        return Department::query()->create($data);
    }
}
