<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequestItem;
use App\Support\VisitWorkflowGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReviewLabResultEntry
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
        private VisitWorkflowGuard $visitWorkflowGuard,
    ) {}

    public function handle(LabRequestItem $labRequestItem, string $staffId, ?string $reviewNotes): LabRequestItem
    {
        $resultEntry = $labRequestItem->resultEntry()->first();

        if ($resultEntry === null || ! $resultEntry->values()->exists()) {
            throw ValidationException::withMessages([
                'review' => 'Enter lab results before marking them reviewed.',
            ]);
        }

        if ($labRequestItem->approved_at !== null || $labRequestItem->status === LabRequestItemStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'review' => 'Approved results cannot be reviewed again.',
            ]);
        }

        if ($labRequestItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'review' => 'Rejected specimens cannot move into review until a new sample is collected.',
            ]);
        }

        $tenantId = $labRequestItem->request()->value('tenant_id');
        $releasePolicy = is_string($tenantId) && $tenantId !== ''
            ? $this->visitWorkflowGuard->labReleasePolicy($tenantId)
            : [
                'require_review_before_release' => true,
                'require_approval_before_release' => true,
            ];

        return DB::transaction(function () use ($labRequestItem, $resultEntry, $staffId, $reviewNotes, $releasePolicy): LabRequestItem {
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

            $labRequestItem->forceFill([
                'status' => $shouldAutoRelease ? LabRequestItemStatus::COMPLETED : LabRequestItemStatus::IN_PROGRESS,
                'reviewed_by' => $staffId,
                'reviewed_at' => $timestamp,
                'approved_by' => $shouldAutoRelease ? $staffId : null,
                'approved_at' => $shouldAutoRelease ? $timestamp : null,
                'completed_at' => $shouldAutoRelease ? $timestamp : null,
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh();
        });
    }
}
