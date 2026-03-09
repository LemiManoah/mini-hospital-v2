<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Staff;

final class UpdateStaff
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Staff $staff, array $data): Staff
    {
        $staff->update($data);

        return $staff;
    }
}
