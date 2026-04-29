<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\PrescriptionItem;

final readonly class SyncPrescriptionCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(Prescription $prescription): void
    {
        $prescription->loadMissing([
            'visit.payer',
            'items.inventoryItem:id,default_selling_price',
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

        $total = $chargeableItems->sum(function (PrescriptionItem $item) use ($visit): float {
            $inventoryItem = $item->inventoryItem;

            if (! $inventoryItem instanceof InventoryItem) {
                return 0.0;
            }

            $unitPrice = $this->resolveVisitChargeAmount->handle(
                $visit,
                BillableItemType::DRUG,
                $inventoryItem->id,
                $inventoryItem->default_selling_price === null ? null : (float) $inventoryItem->default_selling_price,
            );

            if ($unitPrice === null) {
                return 0.0;
            }

            return round($unitPrice * (float) $item->quantity, 2);
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
        );
    }
}
