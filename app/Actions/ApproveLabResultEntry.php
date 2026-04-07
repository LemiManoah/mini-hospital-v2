<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Models\LabRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApproveLabResultEntry
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
    ) {}

    public function handle(
        LabRequestItem $labRequestItem,
        string $staffId,
        ?string $reviewNotes,
        ?string $approvalNotes,
    ): LabRequestItem
    {
        $resultEntry = $labRequestItem->resultEntry()->first();

        if ($resultEntry === null || ! $resultEntry->values()->exists()) {
            throw ValidationException::withMessages([
                'approve' => 'Enter results before approving and releasing them.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $resultEntry, $staffId, $reviewNotes, $approvalNotes): LabRequestItem {
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

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::COMPLETED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => $reviewTimestamp,
                'approved_by' => $staffId,
                'approved_at' => $timestamp,
                'completed_at' => $timestamp,
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh();
        });
    }
}
