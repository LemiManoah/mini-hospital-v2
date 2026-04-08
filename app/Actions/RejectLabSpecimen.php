<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RejectLabSpecimen
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
    ) {}

    public function handle(LabRequestItem $labRequestItem, string $staffId, string $rejectionReason): LabRequestItem
    {
        $specimen = $labRequestItem->specimen()->first();

        if ($specimen === null) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Pick a sample before rejecting it.',
            ]);
        }

        if ($specimen->status === LabSpecimenStatus::REJECTED) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'This specimen has already been rejected.',
            ]);
        }

        if (
            $labRequestItem->result_entered_at !== null
            || $labRequestItem->reviewed_at !== null
            || $labRequestItem->approved_at !== null
            || $labRequestItem->status === LabRequestItemStatus::COMPLETED
        ) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Samples with active or released results cannot be rejected from this workflow.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $specimen, $staffId, $rejectionReason): LabRequestItem {
            $specimen->forceFill([
                'status' => LabSpecimenStatus::REJECTED,
                'rejected_by' => $staffId,
                'rejected_at' => now(),
                'rejection_reason' => $rejectionReason,
            ])->save();

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::PENDING,
                'received_by' => null,
                'received_at' => null,
                'result_entered_by' => null,
                'result_entered_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'completed_at' => null,
            ])->save();

            $this->syncLabRequestProgress->handle($labRequestItem->request()->firstOrFail());

            return $labRequestItem->refresh()->loadMissing([
                'specimen',
                'specimen.collectedBy',
                'specimen.rejectedBy',
            ]);
        });
    }
}
