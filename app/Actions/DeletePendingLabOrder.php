<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabOrder;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingLabOrder
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabOrder $labOrder): void
    {
        $visit = $labOrder->visit()->with(['payer', 'billing'])->firstOrFail();
        $oldValues = [
            'test_ids' => $labOrder->items()->pluck('test_id')->all(),
            'clinical_notes' => $labOrder->clinical_notes,
            'priority' => $labOrder->priority?->value,
            'diagnosis_code' => $labOrder->diagnosis_code,
            'is_stat' => $labOrder->is_stat,
        ];

        DB::transaction(function () use ($labOrder, $visit, $oldValues): void {
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_order.deleted',
                subject: $labOrder,
                description: 'Laboratory request removed.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $labOrder->requested_by,
                oldValues: $oldValues,
            );

            VisitCharge::query()
                ->where('patient_visit_id', $labOrder->visit_id)
                ->where('source_type', $labOrder->getMorphClass())
                ->where('source_id', $labOrder->id)
                ->delete();

            $labOrder->items()->delete();
            $labOrder->delete();

            $billing = $this->ensureVisitBilling->handle($visit);
            $this->recalculateVisitBilling->handle($billing);
        });
    }
}
