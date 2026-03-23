<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FacilityServiceOrder;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingFacilityServiceOrder
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
    ) {}

    public function handle(FacilityServiceOrder $order): void
    {
        $visit = $order->visit()->with(['payer', 'billing'])->firstOrFail();

        DB::transaction(function () use ($order, $visit): void {
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
