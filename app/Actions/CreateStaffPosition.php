<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StaffPosition;

final class CreateStaffPosition
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): StaffPosition
    {
        return StaffPosition::query()->create($data);
    }
}
