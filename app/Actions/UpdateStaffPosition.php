<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StaffPosition;

final class UpdateStaffPosition
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(StaffPosition $staffPosition, array $data): bool
    {
        return $staffPosition->update($data);
    }
}
