<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class InventoryRequisitionAccess
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryRequisitionWorkflow $inventoryRequisitionWorkflow,
    ) {}

    /**
     * @return list<string>
     */
    public function indexLocationIds(?User $user, InventoryWorkspace $workspace, ?string $branchId): array
    {
        return $workspace->isInventory()
            ? $this->inventoryLocationAccess->accessibleLocationIds(
                $user,
                $branchId,
                $this->inventoryRequisitionWorkflow->fulfillingLocationTypes(),
            )
            : $this->inventoryLocationAccess->accessibleLocationIds(
                $user,
                $branchId,
                $workspace->locationTypeValues(),
            );
    }

    /**
     * @return Collection<int, InventoryLocation>
     */
    public function fulfillingLocations(?User $user, ?string $branchId): Collection
    {
        return $this->inventoryLocationAccess->requisitionFulfillingLocations(
            $user,
            $branchId,
            $this->inventoryRequisitionWorkflow->fulfillingLocationTypes(),
        );
    }

    /**
     * @return Collection<int, InventoryLocation>
     */
    public function requestingLocations(?User $user, InventoryWorkspace $workspace, ?string $branchId): Collection
    {
        return $this->inventoryLocationAccess->accessibleLocations(
            $user,
            $branchId,
            $workspace->locationTypeValues(),
        );
    }

    public function canView(?User $user, InventoryRequisition $requisition, InventoryWorkspace $workspace): bool
    {
        if ($workspace->isInventory()) {
            return $this->canViewIncomingQueue($user, $requisition);
        }

        return $this->inventoryLocationAccess->canViewRequestedRequisition($user, $requisition, $requisition->branch_id);
    }

    public function canProcess(?User $user, InventoryRequisition $requisition): bool
    {
        return $this->inventoryLocationAccess->canFulfillRequisition($user, $requisition, $requisition->branch_id);
    }

    public function matchesWorkspace(InventoryRequisition $requisition, InventoryWorkspace $workspace): bool
    {
        if ($workspace->isInventory()) {
            return $this->inventoryRequisitionWorkflow->isIncomingQueueItem($requisition);
        }

        $workspaceTypes = $workspace->locationTypeValues();
        if ($workspaceTypes === []) {
            return true;
        }

        $fulfillingType = $requisition->fulfillingLocation?->type?->value;
        $requestingType = $requisition->requestingLocation?->type?->value;

        return in_array($fulfillingType, $workspaceTypes, true)
            || in_array($requestingType, $workspaceTypes, true);
    }

    public function isIncomingQueueItem(InventoryRequisition $requisition): bool
    {
        return $this->inventoryRequisitionWorkflow->isIncomingQueueItem($requisition);
    }

    private function canViewIncomingQueue(?User $user, InventoryRequisition $requisition): bool
    {
        if ($this->inventoryLocationAccess->canFulfillRequisition($user, $requisition, $requisition->branch_id)) {
            return true;
        }

        return $user instanceof User && ($user->isSupportUser() || $user->hasAnyRole(['super_admin', 'admin', 'store_keeper']));
    }
}
