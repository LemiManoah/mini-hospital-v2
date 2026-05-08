<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabOrderItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabOrderItem;
use App\Support\VisitWorkflowGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReviewLabResultEntry
{
    public function __construct(
        private SyncLabOrderProgress $syncLabOrderProgress,
        private VisitWorkflowGuard $visitWorkflowGuard,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabOrderItem $labOrderItem, string $staffId, ?string $reviewNotes): LabOrderItem
    {
        $resultEntry = $labOrderItem->resultEntry()->first();

        if ($resultEntry === null || ! $resultEntry->values()->exists()) {
            throw ValidationException::withMessages([
                'review' => 'Enter lab results before marking them reviewed.',
            ]);
        }

        if ($labOrderItem->approved_at !== null || $labOrderItem->status === LabOrderItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'review' => 'Approved results cannot be reviewed again.',
            ]);
        }

        if ($labOrderItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'review' => 'Rejected specimens cannot move into review until a new sample is collected.',
            ]);
        }

        $tenantId = $labOrderItem->order()->value('tenant_id');
        $releasePolicy = is_string($tenantId) && $tenantId !== ''
            ? $this->visitWorkflowGuard->labReleasePolicy($tenantId)
            : [
                'require_review_before_release' => true,
                'require_approval_before_release' => true,
            ];

        return DB::transaction(function () use ($labOrderItem, $resultEntry, $staffId, $reviewNotes, $releasePolicy): LabOrderItem {
            $timestamp = now();
            $shouldAutoRelease = ! $releasePolicy['require_approval_before_release'];

            $resultEntry->forceFill([
                'reviewed_by' => $staffId,
                'reviewed_at' => $timestamp,
                'review_notes' => $reviewNotes,
                'approved_by' => $shouldAutoRelease ? $staffId : null,
                'approved_at' => $shouldAutoRelease ? $timestamp : null,
                'released_by' => $shouldAutoRelease ? $staffId : null,
                'released_at' => $shouldAutoRelease ? $timestamp : null,
            ])->save();

            $labOrderItem->forceFill([
                'status' => $shouldAutoRelease ? LabOrderItemStatus::COMPLETED : LabOrderItemStatus::IN_PROGRESS,
                'reviewed_by' => $staffId,
                'reviewed_at' => $timestamp,
                'approved_by' => $shouldAutoRelease ? $staffId : null,
                'approved_at' => $shouldAutoRelease ? $timestamp : null,
                'completed_at' => $shouldAutoRelease ? $timestamp : null,
            ])->save();

            $labOrder = $labOrderItem->order()->firstOrFail();
            $this->syncLabOrderProgress->handle($labOrder);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_result.reviewed',
                subject: $resultEntry,
                description: $shouldAutoRelease
                    ? 'Lab result reviewed and released.'
                    : 'Lab result reviewed.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_order_id' => $labOrder->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'lab_result_entry_id' => $resultEntry->id,
                    'reviewed_by' => $staffId,
                    'reviewed_at' => $timestamp->toISOString(),
                    'approved_at' => $resultEntry->approved_at?->toISOString(),
                    'released_at' => $resultEntry->released_at?->toISOString(),
                ],
                metadata: [
                    'review_notes' => $reviewNotes,
                    'causer_user_id' => Auth::id(),
                ],
            );

            if ($shouldAutoRelease) {
                $this->recordAuditActivity->handle(
                    logName: 'laboratory',
                    event: 'lab_result.released',
                    subject: $resultEntry,
                    description: 'Lab result released.',
                    tenantId: $labOrder->tenant_id,
                    branchId: $labOrder->facility_branch_id,
                    staffId: $staffId,
                    newValues: [
                        'lab_order_id' => $labOrder->id,
                        'lab_order_item_id' => $labOrderItem->id,
                        'lab_result_entry_id' => $resultEntry->id,
                        'released_at' => $timestamp->toISOString(),
                    ],
                    metadata: [
                        'released_via' => 'review',
                        'causer_user_id' => Auth::id(),
                    ],
                );
            }

            return $labOrderItem->refresh();
        });
    }
}
