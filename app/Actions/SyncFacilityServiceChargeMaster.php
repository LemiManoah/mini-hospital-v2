<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\ChargeMaster;
use App\Models\FacilityService;
use Illuminate\Support\Facades\Auth;

final class SyncFacilityServiceChargeMaster
{
    public function handle(FacilityService $service): ?ChargeMaster
    {
        $actorId = Auth::id() ?? $service->updated_by ?? $service->created_by;

        if (! $service->is_billable) {
            ChargeMaster::query()
                ->whereKey($service->charge_master_id ?? $service->id)
                ->update([
                    'is_active' => false,
                    'updated_by' => $actorId,
                    'updated_at' => now(),
                ]);

            if ($service->charge_master_id !== null) {
                $service->forceFill([
                    'charge_master_id' => null,
                    'updated_by' => $actorId,
                ])->save();
            }

            return null;
        }

        $chargeMasterId = $service->charge_master_id ?? $service->id;

        $chargeMaster = ChargeMaster::query()->updateOrCreate(
            [
                'id' => $chargeMasterId,
            ],
            [
                'tenant_id' => $service->tenant_id,
                'facility_branch_id' => null,
                'item_code' => $service->service_code,
                'description' => $service->name,
                'billable_type' => BillableItemType::SERVICE,
                'billable_id' => $service->id,
                'unit_price' => $service->selling_price ?? 0,
                'is_active' => $service->is_active,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'created_by' => $service->created_by ?? $actorId,
                'updated_by' => $actorId,
            ],
        );

        if ($service->charge_master_id !== $chargeMaster->id) {
            $service->forceFill([
                'charge_master_id' => $chargeMaster->id,
                'updated_by' => $actorId,
            ])->save();
        }

        return $chargeMaster;
    }
}
