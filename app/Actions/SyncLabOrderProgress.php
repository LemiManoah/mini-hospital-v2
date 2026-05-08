<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabOrderItemStatus;
use App\Enums\LabOrderStatus;
use App\Enums\LabSpecimenStatus;
use App\Models\LabOrder;
use App\Models\LabOrderItem;

final readonly class SyncLabOrderProgress
{
    public function handle(LabOrder $labOrder): LabOrder
    {
        $items = $labOrder->items()
            ->with(['specimen:id,lab_order_item_id,status'])
            ->get(['id', 'status', 'completed_at', 'received_at', 'result_entered_at', 'reviewed_at', 'approved_at']);

        if ($items->isEmpty()) {
            return $labOrder;
        }

        if ($items->every(static fn (LabOrderItem $item): bool => $item->status === LabOrderItemStatus::CANCELLED)) {
            $labOrder->forceFill([
                'status' => LabOrderStatus::CANCELLED,
                'completed_at' => null,
            ])->save();

            return $labOrder->refresh();
        }

        if ($items->every(static fn (LabOrderItem $item): bool => $item->status === LabOrderItemStatus::COMPLETED)) {
            $labOrder->forceFill([
                'status' => LabOrderStatus::COMPLETED,
                'completed_at' => $items->max('completed_at') ?? now(),
            ])->save();

            return $labOrder->refresh();
        }

        if ($items->contains(static fn (LabOrderItem $item): bool => $item->status === LabOrderItemStatus::IN_PROGRESS
            || $item->status === LabOrderItemStatus::COMPLETED
            || $item->result_entered_at !== null
            || $item->reviewed_at !== null
            || $item->approved_at !== null)) {
            $labOrder->forceFill([
                'status' => LabOrderStatus::IN_PROGRESS,
                'completed_at' => null,
            ])->save();

            return $labOrder->refresh();
        }

        if ($items->contains(static fn (LabOrderItem $item): bool => $item->received_at !== null)) {
            $labOrder->forceFill([
                'status' => LabOrderStatus::SAMPLE_COLLECTED,
                'completed_at' => null,
            ])->save();

            return $labOrder->refresh();
        }

        if ($items->every(static fn (LabOrderItem $item): bool => $item->specimen?->status === LabSpecimenStatus::REJECTED)) {
            $labOrder->forceFill([
                'status' => LabOrderStatus::REJECTED,
                'completed_at' => null,
            ])->save();

            return $labOrder->refresh();
        }

        $labOrder->forceFill([
            'status' => LabOrderStatus::REQUESTED,
            'completed_at' => null,
        ])->save();

        return $labOrder->refresh();
    }
}
