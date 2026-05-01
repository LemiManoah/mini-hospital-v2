<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabRequest;
use App\Models\User;
use App\Models\VisitCharge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingLabRequest
{
    public function __construct(
        private EnsureVisitBilling $ensureVisitBilling,
        private RecalculateVisitBilling $recalculateVisitBilling,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabRequest $labRequest): void
    {
        $visit = $labRequest->visit()->with(['payer', 'billing'])->firstOrFail();
        $oldValues = [
            'test_ids' => $labRequest->items()->pluck('test_id')->all(),
            'clinical_notes' => $labRequest->clinical_notes,
            'priority' => $labRequest->priority?->value,
            'diagnosis_code' => $labRequest->diagnosis_code,
            'is_stat' => $labRequest->is_stat,
        ];

        DB::transaction(function () use ($labRequest, $visit, $oldValues): void {
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_request.deleted',
                subject: $labRequest,
                description: 'Laboratory request removed.',
                tenantId: $labRequest->tenant_id,
                branchId: $labRequest->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $labRequest->requested_by,
                oldValues: $oldValues,
            );

            VisitCharge::query()
                ->where('patient_visit_id', $labRequest->visit_id)
                ->where('source_type', $labRequest->getMorphClass())
                ->where('source_id', $labRequest->id)
                ->delete();

            $labRequest->items()->delete();
            $labRequest->delete();

            $billing = $this->ensureVisitBilling->handle($visit);
            $this->recalculateVisitBilling->handle($billing);
        });
    }
}
