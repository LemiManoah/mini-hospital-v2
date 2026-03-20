<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityBranch;

final readonly class UpdateFacilityBranch
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(FacilityBranch $facilityBranch, array $data): bool
    {
        return $facilityBranch->update($data);
    }
}
