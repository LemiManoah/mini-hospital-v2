<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\ChargeMaster;
use App\Models\ImagingStudyCatalog;
use Illuminate\Support\Facades\Auth;

final class SyncImagingStudyCatalogChargeMaster
{
    public function handle(ImagingStudyCatalog $studyCatalog): ?ChargeMaster
    {
        if (! $studyCatalog->is_active) {
            ChargeMaster::query()
                ->whereKey($studyCatalog->charge_master_id ?? $studyCatalog->id)
                ->update([
                    'is_active' => false,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            return null;
        }

        $chargeMasterId = $studyCatalog->charge_master_id ?? $studyCatalog->id;

        $chargeMaster = ChargeMaster::query()->updateOrCreate(
            [
                'id' => $chargeMasterId,
            ],
            [
                'tenant_id' => $studyCatalog->tenant_id,
                'facility_branch_id' => $studyCatalog->facility_branch_id,
                'item_code' => $studyCatalog->code,
                'description' => $studyCatalog->name,
                'billable_type' => BillableItemType::IMAGING,
                'billable_id' => $studyCatalog->id,
                'unit_price' => $studyCatalog->base_price ?? 0,
                'is_active' => $studyCatalog->is_active,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ],
        );

        if ($studyCatalog->charge_master_id !== $chargeMaster->id) {
            $studyCatalog->forceFill([
                'charge_master_id' => $chargeMaster->id,
            ])->save();
        }

        return $chargeMaster;
    }
}
