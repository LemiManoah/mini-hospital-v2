<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Staff;

final class CreateStaff
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Staff
    {
        return Staff::create($data);
    }
}
