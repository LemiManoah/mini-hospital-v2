<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use Illuminate\Support\Facades\Auth;

final readonly class UpsertChargeMasterVersion
{
    /**
     * @param  array{
     *     id: string,
     *     tenant_id: string|null,
     *     facility_branch_id: string|null,
     *     item_code: string,
     *     description: string,
     *     billable_type: mixed,
     *     billable_id: string|null,
     *     unit_price: int|float|string,
     *     is_active: bool,
     *     effective_from: string|null,
     *     effective_to: string|null,
     *     created_by?: int|string|null,
     *     updated_by?: int|string|null
     * }  $attributes
     */
    public function handle(?ChargeMaster $current, array $attributes, bool $forceNewVersion = false): ChargeMaster
    {
        if ($current instanceof ChargeMaster && ($forceNewVersion || $this->priceChanged($current, $attributes['unit_price']))) {
            $current->forceFill([
                'is_active' => false,
                'effective_to' => now()->subDay()->toDateString(),
                'updated_by' => $attributes['updated_by'] ?? Auth::id(),
            ])->save();

            unset($attributes['id']);

            /** @var ChargeMaster $newChargeMaster */
            $newChargeMaster = ChargeMaster::query()->create($attributes);

            return $newChargeMaster;
        }

        /** @var ChargeMaster $chargeMaster */
        $chargeMaster = ChargeMaster::query()->updateOrCreate(
            ['id' => $attributes['id']],
            $attributes,
        );

        return $chargeMaster;
    }

    private function priceChanged(ChargeMaster $chargeMaster, int|float|string $unitPrice): bool
    {
        return round((float) $chargeMaster->unit_price, 2) !== round((float) $unitPrice, 2);
    }
}
