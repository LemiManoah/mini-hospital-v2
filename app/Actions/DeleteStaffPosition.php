<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StaffPosition;

final class DeleteStaffPosition
{
    public function handle(StaffPosition $staffPosition): ?bool
    {
        return $staffPosition->delete();
    }
}
