<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InventoryLocationType;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\User;
use Illuminate\Support\Collection;

final class InventoryLocationAccess
{
    /**
     * @return Collection<int, InventoryLocation>
     */
    public function accessibleLocations(?User $user, ?string $branchId = null, array $requestedTypes = []): Collection
    {
        $branchId ??= BranchContext::getActiveBranchId();

        if (! is_string($branchId) || $branchId === '') {
            return collect();
        }

        $query = InventoryLocation::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name');

        $normalizedRequestedTypes = $this->normalizeTypes($requestedTypes);

        if ($normalizedRequestedTypes !== []) {
            $query->whereIn('type', $normalizedRequestedTypes);
        }

        if ($this->hasBroadAccess($user)) {
            return $query->get();
        }

        $types = $this->restrictedTypes($user);

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    public function accessibleLocationIds(?User $user, ?string $branchId = null, array $requestedTypes = []): array
    {
        return $this->accessibleLocations($user, $branchId, $requestedTypes)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, InventoryLocation>
     */
    public function requisitionSourceLocations(?User $user, ?string $branchId = null, array $requestedTypes = []): Collection
    {
        $branchId ??= BranchContext::getActiveBranchId();

        if (! is_string($branchId) || $branchId === '') {
            return collect();
        }

        $query = InventoryLocation::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('name');

        $normalizedRequestedTypes = $this->normalizeTypes($requestedTypes);

        if ($normalizedRequestedTypes !== []) {
            $query->whereIn('type', $normalizedRequestedTypes);
        } elseif (! $this->hasBroadAccess($user) && $this->restrictedTypes($user) !== []) {
            $query->where('type', InventoryLocationType::MAIN_STORE);
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    public function requisitionSourceLocationIds(?User $user, ?string $branchId = null, array $requestedTypes = []): array
    {
        return $this->requisitionSourceLocations($user, $branchId, $requestedTypes)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();
    }

    public function canAccessLocation(?User $user, ?string $locationId, ?string $branchId = null): bool
    {
        return is_string($locationId)
            && $locationId !== ''
            && in_array($locationId, $this->accessibleLocationIds($user, $branchId), true);
    }

    public function canAccessLocationForTypes(?User $user, ?string $locationId, array $allowedTypes, ?string $branchId = null): bool
    {
        return is_string($locationId)
            && $locationId !== ''
            && in_array($locationId, $this->accessibleLocationIds($user, $branchId, $allowedTypes), true);
    }

    public function canCreateRequisition(?User $user, ?string $sourceLocationId, ?string $destinationLocationId, ?string $branchId = null): bool
    {
        return is_string($sourceLocationId)
            && $sourceLocationId !== ''
            && is_string($destinationLocationId)
            && $destinationLocationId !== ''
            && in_array($sourceLocationId, $this->requisitionSourceLocationIds($user, $branchId), true)
            && in_array($destinationLocationId, $this->accessibleLocationIds($user, $branchId), true);
    }

    public function canCreateRequisitionForTypes(
        ?User $user,
        ?string $sourceLocationId,
        ?string $destinationLocationId,
        array $destinationTypes,
        ?string $branchId = null,
    ): bool {
        return is_string($sourceLocationId)
            && $sourceLocationId !== ''
            && is_string($destinationLocationId)
            && $destinationLocationId !== ''
            && in_array(
                $sourceLocationId,
                $this->requisitionSourceLocationIds($user, $branchId, [InventoryLocationType::MAIN_STORE]),
                true,
            )
            && in_array(
                $destinationLocationId,
                $this->accessibleLocationIds($user, $branchId, $destinationTypes),
                true,
            );
    }

    public function canViewRequisition(?User $user, InventoryRequisition $requisition, ?string $branchId = null): bool
    {
        $locationIds = $this->accessibleLocationIds($user, $branchId);

        return in_array($requisition->source_inventory_location_id, $locationIds, true)
            || in_array($requisition->destination_inventory_location_id, $locationIds, true);
    }

    public function canProcessRequisition(?User $user, InventoryRequisition $requisition, ?string $branchId = null): bool
    {
        return in_array(
            $requisition->source_inventory_location_id,
            $this->accessibleLocationIds($user, $branchId),
            true,
        );
    }

    private function hasBroadAccess(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSupportUser()) {
            return true;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'store_keeper']);
    }

    /**
     * @return list<string>
     */
    private function restrictedTypes(?User $user): array
    {
        if (! $user instanceof User || $this->hasBroadAccess($user)) {
            return [];
        }

        $types = collect();

        if ($user->hasRole('pharmacist')) {
            $types->push(InventoryLocationType::PHARMACY->value);
        }

        if ($user->hasRole('lab_technician')) {
            $types->push(InventoryLocationType::LABORATORY->value);
        }

        return $types
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, InventoryLocationType|string>  $types
     * @return list<string>
     */
    private function normalizeTypes(array $types): array
    {
        return collect($types)
            ->map(static fn (InventoryLocationType|string $type): string => $type instanceof InventoryLocationType
                ? $type->value
                : $type)
            ->filter(static fn (string $type): bool => $type !== '')
            ->unique()
            ->values()
            ->all();
    }
}
