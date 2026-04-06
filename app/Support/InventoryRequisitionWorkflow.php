<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InventoryLocationType;
use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use Illuminate\Database\Eloquent\Builder;

final class InventoryRequisitionWorkflow
{
    /**
     * @return list<string>
     */
    public function requesterLocationTypes(): array
    {
        return [
            InventoryLocationType::PHARMACY->value,
            InventoryLocationType::LABORATORY->value,
        ];
    }

    /**
     * @return list<string>
     */
    public function fulfillingLocationTypes(): array
    {
        return [InventoryLocationType::MAIN_STORE->value];
    }

    /**
     * @return list<string>
     */
    public function hiddenIncomingStatuses(): array
    {
        return [
            InventoryRequisitionStatus::Draft->value,
            InventoryRequisitionStatus::Cancelled->value,
        ];
    }

    public function applyIncomingQueueScope(Builder $query, array $fulfillingLocationIds): void
    {
        $query
            ->whereIn('source_inventory_location_id', $fulfillingLocationIds)
            ->whereNotIn('status', $this->hiddenIncomingStatuses())
            ->whereHas('requestingLocation', function (Builder $locationQuery): void {
                $locationQuery->whereIn('type', $this->requesterLocationTypes());
            });
    }

    public function isIncomingQueueItem(InventoryRequisition $requisition): bool
    {
        $destinationType = $requisition->requestingLocation?->type?->value;
        $status = $requisition->status?->value;

        return ! in_array($status, $this->hiddenIncomingStatuses(), true)
            && in_array($destinationType, $this->requesterLocationTypes(), true);
    }
}
