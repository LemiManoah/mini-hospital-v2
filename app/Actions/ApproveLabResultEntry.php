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

    public function handle(LabRequestItem $labRequestItem, string $staffId, ?string $approvalNotes): LabRequestItem
    {
        $resultEntry = $labRequestItem->resultEntry()->first();

        if ($resultEntry === null || ! $resultEntry->values()->exists()) {
            throw ValidationException::withMessages([
                'approve' => 'Enter and review results before approval.',
            ]);
        }

        if ($resultEntry->reviewed_at === null) {
            throw ValidationException::withMessages([
                'approve' => 'Review the result before approving it.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $resultEntry, $staffId, $approvalNotes): LabRequestItem {
            $timestamp = now();

            $resultEntry->forceFill([
                'approved_by' => $staffId,
                'approved_at' => $timestamp,
                'released_by' => $staffId,
                'released_at' => $timestamp,
                'approval_notes' => $approvalNotes,
            ])->save();

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::COMPLETED,
                'approved_by' => $staffId,
                'approved_at' => $timestamp,
                'completed_at' => $timestamp,
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh();
        });
    }
}
