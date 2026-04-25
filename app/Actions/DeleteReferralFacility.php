<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferralFacility;

final class DeleteReferralFacility
{
    public function handle(ReferralFacility $referralFacility): ?bool
    {
        return $referralFacility->delete();
    }
}
