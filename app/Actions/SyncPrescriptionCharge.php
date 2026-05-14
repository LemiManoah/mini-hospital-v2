<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncPrescriptionCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private SyncInventoryItemChargeMaster $syncInventoryItemChargeMaster,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(Prescription $prescription): void
    {
        $prescription->loadMissing([
            'visit.payer',
            'items.inventoryItem:id,tenant_id,item_type,name,generic_name,brand_name,default_selling_price,charge_master_id,is_active,created_by',
            'items.inventoryItem.chargeMaster',
        ]);

        $visit = $prescription->visit;

        if (! $visit instanceof PatientVisit) {
            return;
        }

        $chargeableItems = $prescription->items->filter(
            static fn (PrescriptionItem $item): bool => ! $item->is_external_pharmacy,
        );

        if ($chargeableItems->isEmpty()) {
            return;
        }

        $copayAmount = 0.0;
        $total = $chargeableItems->sum(function (PrescriptionItem $item) use ($visit, &$copayAmount): float {
            $inventoryItem = $item->inventoryItem;

            if (! $inventoryItem instanceof InventoryItem) {
                return 0.0;
            }

            $chargeMaster = $this->chargeMasterFor($inventoryItem);

            if (! $chargeMaster instanceof ChargeMaster) {
                return 0.0;
            }

            $pricing = $this->resolveVisitChargeAmount->resolveChargeMaster($visit, $chargeMaster, (float) $item->quantity);

            if (! $pricing instanceof VisitChargePricing) {
                return 0.0;
            }

            $copayAmount += $pricing->copayAmount;

            return round($pricing->unitPrice * (float) $item->quantity, 2);
        });

        if ($total <= 0) {
            return;
        }

        $description = $chargeableItems->count() === 1
            ? 'Prescription: 1 medication'
            : sprintf('Prescription: %d medications', $chargeableItems->count());

        $this->upsertVisitCharge->handle(
            $visit,
            $prescription,
            $description,
            $total,
            1,
            'PRESCRIPTION',
            copayAmount: $copayAmount,
        );
    }

    private function chargeMasterFor(InventoryItem $inventoryItem): ?ChargeMaster
    {
        $inventoryItem->loadMissing('chargeMaster');

        if ($inventoryItem->chargeMaster instanceof ChargeMaster) {
            return $inventoryItem->chargeMaster;
        }

        return $this->syncInventoryItemChargeMaster->handle($inventoryItem);
    }
}
