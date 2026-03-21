<?php

declare(strict_types=1);

namespace App\Actions;

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

        if ($order->service === null || ! $order->service->is_billable) {
            return;
        }

        $amount = $this->resolveVisitChargeAmount->handle(
            $order->visit,
            BillableItemType::SERVICE,
            $order->facility_service_id,
            null,
        );

        if ($amount === null) {
            return;
        }

        $this->upsertVisitCharge->handle(
            $order->visit,
            $order,
            sprintf('Facility service: %s', $order->service->name),
            $amount,
            1,
            $order->service->service_code,
        );
    }
}
