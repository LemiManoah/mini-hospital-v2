<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\ChargeMaster;
use App\Models\LabTestCatalog;
use Illuminate\Support\Facades\Auth;

final class SyncLabTestCatalogChargeMaster
{
    public function handle(LabTestCatalog $labTestCatalog): ?ChargeMaster
    {
        if (! $labTestCatalog->is_active) {
            ChargeMaster::query()
                ->whereKey($labTestCatalog->charge_master_id ?? $labTestCatalog->id)
                ->update([
                    'is_active' => false,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            return null;
        }

        $chargeMasterId = $labTestCatalog->charge_master_id ?? $labTestCatalog->id;

        $chargeMaster = ChargeMaster::query()->updateOrCreate(
            [
                'id' => $chargeMasterId,
            ],
            [
                'tenant_id' => $labTestCatalog->tenant_id,
                'facility_branch_id' => null,
                'item_code' => $labTestCatalog->test_code,
                'description' => $labTestCatalog->test_name,
                'billable_type' => BillableItemType::TEST,
                'billable_id' => $labTestCatalog->id,
                'unit_price' => $labTestCatalog->base_price ?? 0,
                'is_active' => $labTestCatalog->is_active,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ],
        );

        if ($labTestCatalog->charge_master_id !== $chargeMaster->id) {
            $labTestCatalog->forceFill([
                'charge_master_id' => $chargeMaster->id,
            ])->save();
        }

        return $chargeMaster;
    }
}
