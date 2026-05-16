<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\ChargeMaster;
use App\Models\FacilityService;
use App\Models\ImagingStudyCatalog;
use App\Models\InventoryItem;
use App\Models\LabTestCatalog;
use Carbon\CarbonImmutable;
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
            $effectiveFrom = $this->date($attributes['effective_from'] ?? null) ?? CarbonImmutable::today();
            $effectiveTo = $this->date($attributes['effective_to'] ?? null);
            $isActive = $attributes['is_active'] ?? true;

            if (! $isActive) {
                $chargeMaster->update([
                    'unit_price' => $attributes['unit_price'],
                    'is_active' => false,
                    'effective_from' => $effectiveFrom->toDateString(),
                    'effective_to' => $effectiveTo?->toDateString(),
                    'updated_by' => Auth::id(),
                ]);

                return $chargeMaster->refresh();
            }

            $chargeMaster->forceFill([
                'is_active' => false,
                'effective_to' => $effectiveFrom->subDay()->toDateString(),
                'updated_by' => Auth::id(),
            ])->save();

            /** @var ChargeMaster $newChargeMaster */
            $newChargeMaster = ChargeMaster::query()->create([
                'tenant_id' => $chargeMaster->tenant_id,
                'facility_branch_id' => $chargeMaster->facility_branch_id,
                'item_code' => $chargeMaster->item_code,
                'description' => $chargeMaster->description,
                'billable_type' => $chargeMaster->billable_type,
                'billable_id' => $chargeMaster->billable_id,
                'unit_price' => $attributes['unit_price'],
                'is_active' => $isActive,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => $effectiveTo?->toDateString(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->linkBillableToChargeMaster($newChargeMaster);

            return $newChargeMaster->refresh();
        });
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? CarbonImmutable::parse((string) $value)->startOfDay() : null;
    }

    private function linkBillableToChargeMaster(ChargeMaster $chargeMaster): void
    {
        if (! $chargeMaster->billable_type instanceof BillableItemType || $chargeMaster->billable_id === null) {
            return;
        }

        $model = match ($chargeMaster->billable_type) {
            BillableItemType::SERVICE => FacilityService::query()->find($chargeMaster->billable_id),
            BillableItemType::DRUG => InventoryItem::query()->find($chargeMaster->billable_id),
            BillableItemType::TEST => LabTestCatalog::query()->find($chargeMaster->billable_id),
            BillableItemType::IMAGING => ImagingStudyCatalog::query()->find($chargeMaster->billable_id),
            default => null,
        };

        if ($model === null) {
            return;
        }

        $model->forceFill([
            'charge_master_id' => $chargeMaster->id,
        ])->save();
    }
}
