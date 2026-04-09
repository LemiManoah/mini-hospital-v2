<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FacilityServiceOrderStatus;
use App\Models\FacilityServiceOrder;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateFacilityServiceOrder
{
    public function __construct(
        private SyncFacilityServiceOrderCharge $syncFacilityServiceOrderCharge,
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
    ) {}

    public function handle(FacilityServiceOrder $order, array $data): FacilityServiceOrder
    {
        $hasPendingDuplicate = FacilityServiceOrder::query()
            ->where('visit_id', $order->visit_id)
            ->where('facility_service_id', $data['facility_service_id'])
            ->where('status', FacilityServiceOrderStatus::PENDING->value)
            ->where('id', '!=', $order->id)
            ->exists();

        if ($hasPendingDuplicate) {
            throw ValidationException::withMessages([
                'facility_service_id' => 'This facility service already has a pending order for this visit. Remove or complete the existing order first.',
            ]);
        }

        return DB::transaction(function () use ($order, $data): FacilityServiceOrder {
            VisitCharge::query()
                ->where('patient_visit_id', $order->visit_id)
                ->where('source_type', $order->getMorphClass())
                ->where('source_id', $order->id)
                ->delete();

            $order->forceFill([
                'facility_service_id' => $data['facility_service_id'],
            ])->save();

            $order->unsetRelation('service');
            $order->loadMissing(['visit.payer']);
            $order->load([
                'service:id,name,service_code,category,is_billable,selling_price',
                'orderedBy:id,first_name,last_name',
            ]);

            $this->syncFacilityServiceOrderCharge->handle($order);

            $billing = $this->ensureVisitBilling->handle($order->visit()->with(['payer', 'billing'])->firstOrFail());
            $this->recalculateVisitBilling->handle($billing);

            return $order->refresh()->load([
                'service:id,name,service_code,category,is_billable,selling_price',
                'orderedBy:id,first_name,last_name',
            ]);
        });
    }
}
