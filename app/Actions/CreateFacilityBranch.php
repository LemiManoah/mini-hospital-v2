<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityBranch;

final readonly class CreateFacilityBranch
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): FacilityBranch
    {
        return FacilityBranch::query()->create([
            ...$data,
            'is_main_branch' => false,
        ]);
    }
}
