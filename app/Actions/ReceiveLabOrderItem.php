<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabOrderItemStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReceiveLabOrderItem
{
    public function __construct(
        private SyncLabOrderProgress $syncLabOrderProgress,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabOrderItem $labOrderItem, string $staffId): LabOrderItem
    {
        if ($labOrderItem->status === LabOrderItemStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'status' => 'Cancelled lab items cannot be received.',
            ]);
        }

        if ($labOrderItem->specimen()->where('status', LabSpecimenStatus::REJECTED->value)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Rejected specimens must be recollected before they can be received.',
            ]);
        }

        return DB::transaction(function () use ($labOrderItem, $staffId): LabOrderItem {
            if ($labOrderItem->received_at === null) {
                $labOrderItem->forceFill([
                    'received_by' => $staffId,
                    'received_at' => now(),
                ]);
            }

            $labOrderItem->forceFill([
                'status' => LabOrderItemStatus::IN_PROGRESS,
            ])->save();

            $labOrder = $labOrderItem->order()->firstOrFail();

            $this->syncLabOrderProgress->handle($labOrder);

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_order_item.received',
                subject: $labOrderItem,
                description: 'Lab order item received.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $staffId,
                newValues: [
                    'lab_order_id' => $labOrder->id,
                    'lab_order_item_id' => $labOrderItem->id,
                    'received_at' => $labOrderItem->received_at?->toISOString(),
                    'status' => $labOrderItem->status->value,
                ],
                metadata: [
                    'causer_user_id' => Auth::id(),
                ],
            );

            return $labOrderItem->refresh();
        });
    }
}
