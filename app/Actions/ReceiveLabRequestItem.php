<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabRequestItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReceiveLabRequestItem
{
    public function __construct(
        private SyncLabRequestProgress $syncLabRequestProgress,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabRequestItem $labRequestItem, string $staffId): LabRequestItem
    {
        if ($labRequestItem->status === LabRequestItemStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'status' => 'Cancelled lab items cannot be received.',
            ]);
        }

        if ($labRequestItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Rejected specimens must be recollected before they can be received.',
            ]);
        }

        return DB::transaction(function () use ($labRequestItem, $staffId): LabRequestItem {
            if ($labRequestItem->received_at === null) {
                $labRequestItem->forceFill([
                    'received_by' => $staffId,
                    'received_at' => now(),
                ]);
            }

            $labRequestItem->forceFill([
                'status' => LabRequestItemStatus::IN_PROGRESS,
            ])->save();

            $labRequest = $labRequestItem->request()->firstOrFail();

            $this->syncLabRequestProgress->handle($labRequest);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_request_item.received',
                subject: $labRequestItem,
                description: 'Lab request item received.',
                tenantId: $labRequest->tenant_id,
                branchId: $labRequest->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_request_id' => $labRequest->id,
                    'lab_request_item_id' => $labRequestItem->id,
                    'received_at' => $labRequestItem->received_at?->toISOString(),
                    'status' => $labRequestItem->status->value,
                ],
                metadata: [
                    'causer_user_id' => Auth::id(),
                ],
            );

            return $labRequestItem->refresh();
        });
    }
}
