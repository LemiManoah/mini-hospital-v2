<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItem;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\VisitCharge;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncDispensingRecordCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private SyncInventoryItemChargeMaster $syncInventoryItemChargeMaster,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(DispensingRecord $dispensingRecord): void
    {
        $dispensingRecord->loadMissing([
            'visit.payer',
            'items.inventoryItem.chargeMaster',
            'items.substitutionInventoryItem.chargeMaster',
        ]);

        $visit = $dispensingRecord->visit;

        if (! $visit instanceof PatientVisit) {
            return;
        }

        $dispensingRecord->items->each(function (DispensingRecordItem $item) use ($visit): void {
            if ($item->external_pharmacy || (float) $item->dispensed_quantity <= 0) {
                $this->removeItemCharge($item, $visit);

                return;
            }

            $inventoryItem = $item->substitutionInventoryItem ?? $item->inventoryItem;

            if (! $inventoryItem instanceof InventoryItem) {
                $this->removeItemCharge($item, $visit);

                return;
            }

            $chargeMaster = $this->chargeMasterFor($inventoryItem);

            if (! $chargeMaster instanceof ChargeMaster) {
                $this->removeItemCharge($item, $visit);

                return;
            }

            $pricing = $this->resolveVisitChargeAmount->resolveChargeMaster(
                $visit,
                $chargeMaster,
                (float) $item->dispensed_quantity,
            );

            if (! $pricing instanceof VisitChargePricing) {
                $this->removeItemCharge($item, $visit);

                return;
            }

            $this->upsertVisitCharge->handle(
                $visit,
                $item,
                sprintf('Dispensed medication: %s', $inventoryItem->generic_name ?? $inventoryItem->name ?? $chargeMaster->description),
                $pricing->unitPrice,
                (float) $item->dispensed_quantity,
                $chargeMaster->item_code,
                $chargeMaster->id,
                copayAmount: $pricing->copayAmount,
            );
        });
    }

    private function chargeMasterFor(InventoryItem $inventoryItem): ?ChargeMaster
    {
        $inventoryItem->loadMissing('chargeMaster');

        if ($inventoryItem->chargeMaster instanceof ChargeMaster) {
            return $inventoryItem->chargeMaster;
        }

        return $this->syncInventoryItemChargeMaster->handle($inventoryItem);
    }

    private function removeItemCharge(DispensingRecordItem $item, PatientVisit $visit): void
    {
        VisitCharge::query()
            ->where('patient_visit_id', $visit->id)
            ->where('source_type', $item->getMorphClass())
            ->where('source_id', $item->id)
            ->delete();
    }
}
