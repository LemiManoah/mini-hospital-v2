<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StaffPosition;
use Illuminate\Support\Facades\Auth;

final class CreateStaffPosition
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): StaffPosition
    {
        return StaffPosition::query()->create([
            ...$data,
            'created_by' => Auth::id(),
        ]);
    }
}
