<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReviewLabResultEntry
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
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

        return DB::transaction(function () use ($labRequestItem, $resultEntry, $staffId, $reviewNotes): LabRequestItem {
            $resultEntry->forceFill([
                'reviewed_by' => $staffId,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ])->save();

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::IN_PROGRESS,
                'reviewed_by' => $staffId,
                'reviewed_at' => now(),
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh();
        });
    }
}
