<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityService;
use App\Models\PatientVisit;
use App\Enums\BillableItemType;
use App\Models\FacilityServiceOrder;

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

        $amount = $this->resolveVisitChargeAmount->handle(
            $visit,
            BillableItemType::SERVICE,
            $service->id,
            $service->selling_price === null ? null : (float) $service->selling_price,
        );

        if ($amount === null) {
            return;
        }

        $this->upsertVisitCharge->handle(
            $visit,
            $order,
            sprintf('Facility service: %s', $service->name),
            $amount,
            1,
            $service->service_code,
        );
    }
}
