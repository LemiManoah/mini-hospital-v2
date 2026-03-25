<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LabRequestItemStatus;
use App\Enums\LabRequestStatus;
use App\Models\LabRequest;

final readonly class SyncLabRequestProgress
{
    public function handle(LabRequest $labRequest): LabRequest
    {
        $items = $labRequest->items()
            ->get(['status', 'completed_at']);

        if ($items->isEmpty()) {
            return $labRequest;
        }

        if ($items->every(static fn ($item): bool => $item->status === LabRequestItemStatus::CANCELLED)) {
            $labRequest->forceFill([
                'status' => LabRequestStatus::CANCELLED,
                'completed_at' => null,
            ])->save();

            return $labRequest->refresh();
        }

        if ($items->every(static fn ($item): bool => $item->status === LabRequestItemStatus::COMPLETED)) {
            $labRequest->forceFill([
                'status' => LabRequestStatus::COMPLETED,
                'completed_at' => $items->max('completed_at') ?? now(),
            ])->save();

            return $labRequest->refresh();
        }

        if ($items->contains(static fn ($item): bool => $item->status === LabRequestItemStatus::IN_PROGRESS || $item->status === LabRequestItemStatus::COMPLETED)) {
            $labRequest->forceFill([
                'status' => LabRequestStatus::IN_PROGRESS,
                'completed_at' => null,
            ])->save();

            return $labRequest->refresh();
        }

        $labRequest->forceFill([
            'status' => LabRequestStatus::REQUESTED,
            'completed_at' => null,
        ])->save();

        return $labRequest->refresh();
    }
}
