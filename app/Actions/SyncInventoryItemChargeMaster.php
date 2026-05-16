<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Enums\InventoryItemType;
use App\Models\ChargeMaster;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;

final readonly class SyncInventoryItemChargeMaster
{
    public function __construct(private UpsertChargeMasterVersion $upsertChargeMasterVersion) {}

    public function handle(InventoryItem $inventoryItem, int|float|string|null $unitPrice = null): ?ChargeMaster
    {
        if (! $this->isBillableDrug($inventoryItem, $unitPrice)) {
            $this->deactivateChargeMaster($inventoryItem);

            return null;
        }

        $chargeMasterId = $inventoryItem->charge_master_id ?? $inventoryItem->id;
        $currentChargeMaster = ChargeMaster::query()->find($chargeMasterId);
        $resolvedUnitPrice = $unitPrice
            ?? $currentChargeMaster->unit_price
            ?? $inventoryItem->chargeMaster->unit_price
            ?? 0;

        $chargeMaster = $this->upsertChargeMasterVersion->handle(
            $currentChargeMaster,
            [
                'id' => $chargeMasterId,
                'tenant_id' => $inventoryItem->tenant_id,
                'facility_branch_id' => null,
                'item_code' => $this->itemCode($inventoryItem),
                'description' => $this->description($inventoryItem),
                'billable_type' => BillableItemType::DRUG,
                'billable_id' => $inventoryItem->id,
                'unit_price' => $resolvedUnitPrice,
                'is_active' => $inventoryItem->is_active,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'created_by' => $inventoryItem->created_by ?? Auth::id(),
                'updated_by' => Auth::id(),
            ],
        );

        if ($inventoryItem->charge_master_id !== $chargeMaster->id) {
            $inventoryItem->forceFill([
                'charge_master_id' => $chargeMaster->id,
                'updated_by' => Auth::id(),
            ])->save();
        }

        return $chargeMaster;
    }

    private function isBillableDrug(InventoryItem $inventoryItem, int|float|string|null $unitPrice = null): bool
    {
        return $inventoryItem->item_type === InventoryItemType::DRUG
            && $inventoryItem->is_active
            && ($unitPrice !== null || $inventoryItem->charge_master_id !== null);
    }

    private function deactivateChargeMaster(InventoryItem $inventoryItem): void
    {
        ChargeMaster::query()
            ->whereKey($inventoryItem->charge_master_id ?? $inventoryItem->id)
            ->update([
                'is_active' => false,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);
    }

    private function itemCode(InventoryItem $inventoryItem): string
    {
        return sprintf('DRUG-%s', $inventoryItem->id);
    }

    private function description(InventoryItem $inventoryItem): string
    {
        return $inventoryItem->generic_name
            ?? $inventoryItem->name
            ?? $inventoryItem->brand_name
            ?? 'Drug item';
    }
}
