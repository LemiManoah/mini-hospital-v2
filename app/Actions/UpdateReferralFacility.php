<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferralFacility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateReferralFacility
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ReferralFacility $referralFacility, array $attributes): ReferralFacility
    {
        return DB::transaction(function () use ($referralFacility, $attributes): ReferralFacility {
            $referralFacility->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $referralFacility->refresh();
        });
    }
}
