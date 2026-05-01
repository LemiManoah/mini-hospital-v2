<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityServiceOrder;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingFacilityServiceOrder
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(FacilityServiceOrder $order): void
    {
        $visit = $order->visit()->with(['payer', 'billing'])->firstOrFail();

        DB::transaction(function () use ($order, $visit): void {
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'clinical',
                event: 'service_order.deleted',
                subject: $order,
                description: 'Facility service order removed.',
                tenantId: $order->tenant_id,
                branchId: $order->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $order->ordered_by,
                oldValues: [
                    'facility_service_id' => $order->facility_service_id,
                    'status' => $order->status->value,
                ],
            );

            VisitCharge::query()
                ->where('patient_visit_id', $order->visit_id)
                ->where('source_type', $order->getMorphClass())
                ->where('source_id', $order->id)
                ->delete();

            $order->delete();

            $billing = $this->ensureVisitBilling->handle($visit);
            $this->recalculateVisitBilling->handle($billing);
        });
    }
}
