<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Staff;

final class DeleteStaff
{
    public function handle(Staff $staff): void
    {
        $staff->delete();
    }
}
