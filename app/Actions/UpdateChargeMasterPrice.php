<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateChargeMasterPrice
{
    /**
     * @param  array{
     *     unit_price: int|float|string,
     *     is_active?: bool,
     *     effective_from?: string|null,
     *     effective_to?: string|null
     * }  $attributes
     */
    public function handle(ChargeMaster $chargeMaster, array $attributes): ChargeMaster
    {
        return DB::transaction(function () use ($chargeMaster, $attributes): ChargeMaster {
            $chargeMaster->update([
                'unit_price' => $attributes['unit_price'],
                'is_active' => $attributes['is_active'] ?? $chargeMaster->is_active,
                'effective_from' => $attributes['effective_from'] ?? $chargeMaster->effective_from,
                'effective_to' => $attributes['effective_to'] ?? $chargeMaster->effective_to,
                'updated_by' => Auth::id(),
            ]);

            return $chargeMaster->refresh();
        });
    }
}
