<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StaffPosition;
use Illuminate\Support\Facades\Auth;

final class UpdateStaffPosition
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(StaffPosition $staffPosition, array $data): bool
    {
        return $staffPosition->update([
            ...$data,
            'updated_by' => Auth::id(),
        ]);
    }
}
