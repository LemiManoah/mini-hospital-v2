<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTestCatalog;
use App\Models\PatientVisit;
use App\Models\VisitCharge;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncLabOrderCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private SyncLabTestCatalogChargeMaster $syncLabTestCatalogChargeMaster,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(LabOrder $request): void
    {
        $request->loadMissing(['visit.payer', 'items.test.chargeMaster']);
        $visit = $request->visit;
        if (! $visit instanceof PatientVisit) {
            return;
        }

        $this->removeAggregateCharge($request, $visit);

        $request->items->each(function (LabOrderItem $item) use ($visit): void {
            $chargeMaster = $this->chargeMasterFor($item);

            if (! $chargeMaster instanceof ChargeMaster) {
                $this->removeItemCharge($item, $visit);

                return;
            }

            $pricing = $this->resolveVisitChargeAmount->resolveChargeMaster($visit, $chargeMaster);

            if (! $pricing instanceof VisitChargePricing) {
                $this->removeItemCharge($item, $visit);

                return;
            }

            $this->upsertVisitCharge->handle(
                $visit,
                $item,
                sprintf('Lab test: %s', $item->test->test_name ?? $chargeMaster->description),
                $pricing->unitPrice,
                1,
                $chargeMaster->item_code,
                $chargeMaster->id,
                copayAmount: $pricing->copayAmount,
            );
        });
    }

    private function chargeMasterFor(LabOrderItem $item): ?ChargeMaster
    {
        $test = $item->test;

        if (! $test instanceof LabTestCatalog) {
            return null;
        }

        $test = LabTestCatalog::query()
            ->with('chargeMaster')
            ->find($test->id);

        if (! $test instanceof LabTestCatalog) {
            return null;
        }

        if ($test->chargeMaster instanceof ChargeMaster) {
            return $test->chargeMaster;
        }

        return $this->syncLabTestCatalogChargeMaster->handle($test);
    }

    private function removeAggregateCharge(LabOrder $request, PatientVisit $visit): void
    {
        VisitCharge::query()
            ->where('patient_visit_id', $visit->id)
            ->where('source_type', $request->getMorphClass())
            ->where('source_id', $request->id)
            ->delete();
    }

    private function removeItemCharge(LabOrderItem $item, PatientVisit $visit): void
    {
        VisitCharge::query()
            ->where('patient_visit_id', $visit->id)
            ->where('source_type', $item->getMorphClass())
            ->where('source_id', $item->id)
            ->delete();
    }
}
