<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabOrderItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResultEntry;
use App\Models\User;
use App\Notifications\LabResultReleasedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApproveLabResultEntry
{
    public function __construct(
        private SyncLabOrderProgress $syncLabOrderProgress,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(
        LabOrderItem $labOrderItem,
        string $staffId,
        ?string $reviewNotes,
        ?string $approvalNotes,
    ): LabOrderItem {
        /** @var LabResultEntry|null $resultEntry */
        $resultEntry = $labOrderItem->resultEntry()->first();

        if ($resultEntry === null || ! $resultEntry->values()->exists()) {
            throw ValidationException::withMessages([
                'approve' => 'Enter results before approving and releasing them.',
            ]);
        }

        if ($labOrderItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'approve' => 'Rejected specimens cannot be released. Recollect the sample first.',
            ]);
        }

        /** @var LabOrder|null $labOrder */
        $labOrder = null;

        $labOrderItem = DB::transaction(function () use ($labOrderItem, $resultEntry, $staffId, $reviewNotes, $approvalNotes, &$labOrder): LabOrderItem {
            $timestamp = now();
            $reviewTimestamp = $resultEntry->reviewed_at ?? $timestamp;
            $reviewerId = $resultEntry->reviewed_by ?? $staffId;

            $resultEntry->forceFill([
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $reviewTimestamp,
                'review_notes' => $reviewNotes ?? $resultEntry->review_notes,
                'approved_by' => $staffId,
                'approved_at' => $timestamp,
                'released_by' => $staffId,
                'released_at' => $timestamp,
                'approval_notes' => $approvalNotes,
            ])->save();

            $labOrderItem->forceFill([
                'status' => LabOrderItemStatus::COMPLETED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $reviewTimestamp,
                'approved_by' => $staffId,
                'approved_at' => $timestamp,
                'completed_at' => $timestamp,
            ])->save();

            /** @var LabOrder $resolvedRequest */
            $resolvedRequest = $labOrderItem->order()->firstOrFail();
            $labOrder = $resolvedRequest;

            $this->syncLabOrderProgress->handle($resolvedRequest);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_result.approved',
                subject: $resultEntry,
                description: 'Lab result approved and released.',
                tenantId: $resolvedRequest->tenant_id,
                branchId: $resolvedRequest->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_order_id' => $resolvedRequest->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'lab_result_entry_id' => $resultEntry->id,
                    'reviewed_by' => $reviewerId,
                    'approved_by' => $staffId,
                    'released_by' => $staffId,
                    'reviewed_at' => $reviewTimestamp->toISOString(),
                    'approved_at' => $timestamp->toISOString(),
                    'released_at' => $timestamp->toISOString(),
                ],
                metadata: [
                    'review_notes' => $reviewNotes,
                    'approval_notes' => $approvalNotes,
                    'causer_user_id' => Auth::id(),
                ],
            );

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_result.released',
                subject: $resultEntry,
                description: 'Lab result released.',
                tenantId: $resolvedRequest->tenant_id,
                branchId: $resolvedRequest->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_order_id' => $resolvedRequest->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'lab_result_entry_id' => $resultEntry->id,
                    'released_at' => $timestamp->toISOString(),
                ],
                metadata: [
                    'released_via' => 'approval',
                    'causer_user_id' => Auth::id(),
                ],
            );

            return $labOrderItem->refresh();
        });

        if ($labOrder instanceof LabOrder) {
            $this->notifyRequestingDoctor($labOrder, $labOrderItem);
        }

        return $labOrderItem;
    }

    private function notifyRequestingDoctor(LabOrder $labOrder, LabOrderItem $labOrderItem): void
    {
        $doctor = User::query()
            ->where('staff_id', $labOrder->requested_by)
            ->where('tenant_id', $labOrder->tenant_id)
            ->first();

        $doctor?->notify(new LabResultReleasedNotification($labOrderItem));
    }
}
