<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\FacilityService;
use App\Models\FacilityServiceOrder;
use App\Models\PatientVisit;
use App\ValueObjects\VisitChargePricing;

final readonly class SyncFacilityServiceOrderCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
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

        $pricing = $this->resolveVisitChargeAmount->resolve(
            $visit,
            BillableItemType::SERVICE,
            $service->id,
            $service->selling_price === null ? null : (float) $service->selling_price,
        );

        if (! $pricing instanceof VisitChargePricing) {
            return;
        }

        $this->upsertVisitCharge->handle(
            $visit,
            $order,
            sprintf('Facility service: %s', $service->name),
            $pricing->unitPrice,
            1,
            $service->service_code,
            $service->charge_master_id,
            copayAmount: $pricing->copayAmount,
        );
    }
}
