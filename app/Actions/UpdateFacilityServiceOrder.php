<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\UpdateFacilityServiceOrderDTO;
use App\Enums\FacilityServiceOrderStatus;
use App\Models\FacilityServiceOrder;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateFacilityServiceOrder
{
    public function __construct(
        private SyncFacilityServiceOrderCharge $syncFacilityServiceOrderCharge,
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(FacilityServiceOrder $order, UpdateFacilityServiceOrderDTO $data): FacilityServiceOrder
    {
        $hasPendingDuplicate = FacilityServiceOrder::query()
            ->where('visit_id', $order->visit_id)
            ->where('facility_service_id', $data->facilityServiceId)
            ->where('status', FacilityServiceOrderStatus::PENDING->value)
            ->where('id', '!=', $order->id)
            ->exists();

        if ($hasPendingDuplicate) {
            throw ValidationException::withMessages([
                'facility_service_id' => 'This facility service already has a pending order for this visit. Remove or complete the existing order first.',
            ]);
        }

        return DB::transaction(function () use ($order, $data): FacilityServiceOrder {
            $user = Auth::user();
            $oldValues = [
                'facility_service_id' => $order->facility_service_id,
                'status' => $order->status->value,
            ];

            VisitCharge::query()
                ->where('patient_visit_id', $order->visit_id)
                ->where('source_type', $order->getMorphClass())
                ->where('source_id', $order->id)
                ->delete();

            $order->forceFill([
                'facility_service_id' => $data->facilityServiceId,
            ])->save();

            $order->unsetRelation('service');
            $order->loadMissing(['visit.payer']);
            $order->load([
                'service:id,name,service_code,category,is_billable,selling_price,charge_master_id',
                'orderedBy:id,first_name,last_name',
            ]);

            $this->syncFacilityServiceOrderCharge->handle($order);

            $billing = $this->ensureVisitBilling->handle($order->visit()->with(['payer', 'billing'])->firstOrFail());
            $this->recalculateVisitBilling->handle($billing);

            $order = $order->refresh()->load([
                'service:id,name,service_code,category,is_billable,selling_price,charge_master_id',
                'orderedBy:id,first_name,last_name',
            ]);

            $this->recordAuditActivity->handle(
                logName: 'clinical',
                event: 'service_order.updated',
                subject: $order,
                description: 'Facility service order updated.',
                tenantId: $order->tenant_id,
                branchId: $order->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $order->ordered_by,
                oldValues: $oldValues,
                newValues: [
                    'facility_service_id' => $order->facility_service_id,
                    'status' => $order->status->value,
                ],
            );

            return $order;
        });
    }
}
