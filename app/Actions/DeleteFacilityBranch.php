<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityBranch;

final readonly class DeleteFacilityBranch
{
    public function handle(FacilityBranch $facilityBranch): ?bool
    {
        return $facilityBranch->delete();
    }
}
