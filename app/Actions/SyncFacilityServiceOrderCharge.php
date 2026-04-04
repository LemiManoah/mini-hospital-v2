<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BillableItemType;
use App\Models\FacilityServiceOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final readonly class SyncFacilityServiceOrderCharge
{
    public function __construct(
        private ResolveVisitChargeAmount $resolveVisitChargeAmount,
        private UpsertVisitCharge $upsertVisitCharge,
    ) {}

    public function handle(FacilityServiceOrder $order): void
    {
        $order->loadMissing(['visit.payer']);
        $order->load([
            'service' => static fn (BelongsTo $query): BelongsTo => $query->select([
                'id',
                'name',
                'service_code',
                'is_billable',
                'selling_price',
            ]),
        ]);

        if ($order->service === null || ! $order->service->is_billable) {
            return;
        }

        $amount = $this->resolveVisitChargeAmount->handle(
            $order->visit,
            BillableItemType::SERVICE,
            $order->facility_service_id,
            $order->service->selling_price === null ? null : (float) $order->service->selling_price,
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
