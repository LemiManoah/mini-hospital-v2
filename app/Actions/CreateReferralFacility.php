<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ReferralFacility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateReferralFacility
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): ReferralFacility
    {
        return DB::transaction(fn (): ReferralFacility => ReferralFacility::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
