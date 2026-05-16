<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ChargeMaster;
use App\Models\FacilityService;
use App\Models\FacilityServiceOrder;
use App\Models\PatientVisit;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncFacilityServiceOrderCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private SyncFacilityServiceChargeMaster $syncFacilityServiceChargeMaster,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(FacilityServiceOrder $order): void
    {
        $order->loadMissing(['visit.payer', 'service']);

        $service = $order->service;
        $visit = $order->visit;
        if (! $service instanceof FacilityService || ! $visit instanceof PatientVisit || ! $service->is_billable) {
            return;
        }

        $chargeMaster = $this->chargeMasterFor($service);

        if (! $chargeMaster instanceof ChargeMaster) {
            return;
        }

        $pricing = $this->resolveVisitChargeAmount->resolveChargeMaster($visit, $chargeMaster);

        if (! $pricing instanceof VisitChargePricing) {
            return;
        }

        $this->upsertVisitCharge->handle(
            $visit,
            $order,
            sprintf('Facility service: %s', $service->name),
            $pricing->unitPrice,
            1,
            $chargeMaster->item_code,
            $chargeMaster->id,
            copayAmount: $pricing->copayAmount,
        );
    }

    private function chargeMasterFor(FacilityService $service): ?ChargeMaster
    {
        $service->loadMissing('chargeMaster');

        if ($service->chargeMaster instanceof ChargeMaster) {
            /** @var ChargeMaster|null $chargeMaster */
            $chargeMaster = ChargeMaster::query()
                ->whereKey($service->chargeMaster->getKey())
                ->first();

            return $chargeMaster;
        }

        return $this->syncFacilityServiceChargeMaster->handle($service);
    }
}
