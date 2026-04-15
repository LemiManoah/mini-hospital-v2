<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use App\Models\PrescriptionItem;

final class PrescriptionDispenseStatusResolver
{
    public function itemStatus(
        float $dispensedQuantity,
        float $prescribedQuantity,
        PrescriptionItemStatus|string|null $fallbackStatus = null,
    ): PrescriptionItemStatus {
        if ($dispensedQuantity <= 0) {
            return $fallbackStatus instanceof PrescriptionItemStatus
                ? $fallbackStatus
                : PrescriptionItemStatus::PENDING;
        }

        if ($dispensedQuantity >= $prescribedQuantity) {
            return PrescriptionItemStatus::DISPENSED;
        }

        return PrescriptionItemStatus::PARTIAL;
    }

    public function prescriptionStatus(Prescription $prescription): PrescriptionStatus
    {
        $items = $prescription->items;

        if ($items->isEmpty()) {
            return PrescriptionStatus::PENDING;
        }

        $statuses = $items
            ->map(static fn (PrescriptionItem $item): ?string => $item->status?->value)
            ->filter()
            ->values();

        if ($statuses->every(static fn (string $status): bool => $status === PrescriptionItemStatus::CANCELLED->value)) {
            return PrescriptionStatus::CANCELLED;
        }

        if ($statuses->every(static fn (string $status): bool => in_array($status, [
            PrescriptionItemStatus::DISPENSED->value,
            PrescriptionItemStatus::CANCELLED->value,
        ], true))) {
            return PrescriptionStatus::FULLY_DISPENSED;
        }

        if ($statuses->contains(PrescriptionItemStatus::PARTIAL->value) || $statuses->contains(PrescriptionItemStatus::DISPENSED->value)) {
            return PrescriptionStatus::PARTIALLY_DISPENSED;
        }

        return PrescriptionStatus::PENDING;
    }
}
