<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\InventoryLocationType;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class InventoryLocationAccess
{
    /**
     * @param  list<InventoryLocationType|string>  $requestedTypes
     * @return EloquentCollection<int, InventoryLocation>
     */
    public function accessibleLocations(?User $user, ?string $branchId = null, array $requestedTypes = []): Collection
    {
        $branchId ??= BranchContext::getActiveBranchId();

        if (! is_string($branchId) || $branchId === '') {
            return new EloquentCollection();
        }

        /** @var Builder<InventoryLocation> $query */
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
     * @param  list<InventoryLocationType|string>  $requestedTypes
     * @return list<string>
     */
    public function accessibleLocationIds(?User $user, ?string $branchId = null, array $requestedTypes = []): array
    {
        return array_values($this->accessibleLocations($user, $branchId, $requestedTypes)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all());
    }

    /**
     * @param  list<InventoryLocationType|string>  $requestedTypes
     * @return EloquentCollection<int, InventoryLocation>
     */
    public function requisitionFulfillingLocations(?User $user, ?string $branchId = null, array $requestedTypes = []): Collection
    {
        $branchId ??= BranchContext::getActiveBranchId();

        if (! is_string($branchId) || $branchId === '') {
            return new EloquentCollection();
        }

        /** @var Builder<InventoryLocation> $query */
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
     * @param  list<InventoryLocationType|string>  $requestedTypes
     * @return list<string>
     */
    public function requisitionFulfillingLocationIds(?User $user, ?string $branchId = null, array $requestedTypes = []): array
    {
        return array_values($this->requisitionFulfillingLocations($user, $branchId, $requestedTypes)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all());
    }

    public function canAccessLocation(?User $user, ?string $locationId, ?string $branchId = null): bool
    {
        return is_string($locationId)
            && $locationId !== ''
            && in_array($locationId, $this->accessibleLocationIds($user, $branchId), true);
    }

    /**
     * @param  list<InventoryLocationType|string>  $allowedTypes
     */
    public function canAccessLocationForTypes(?User $user, ?string $locationId, array $allowedTypes, ?string $branchId = null): bool
    {
        return is_string($locationId)
            && $locationId !== ''
            && in_array($locationId, $this->accessibleLocationIds($user, $branchId, $allowedTypes), true);
    }

    /**
     * @param  list<InventoryLocationType|string>  $requestingTypes
     */
    public function canCreateRequestedRequisition(
        ?User $user,
        ?string $fulfillingLocationId,
        ?string $requestingLocationId,
        array $requestingTypes = [],
        ?string $branchId = null,
    ): bool {
        if (
            ! is_string($fulfillingLocationId)
            || $fulfillingLocationId === ''
            || ! is_string($requestingLocationId)
            || $requestingLocationId === ''
        ) {
            return false;
        }

        $normalizedRequestingTypes = $this->normalizeTypes($requestingTypes);

        $allowedFulfillingLocationIds = $normalizedRequestingTypes === []
            ? $this->requisitionFulfillingLocationIds($user, $branchId)
            : $this->requisitionFulfillingLocationIds($user, $branchId, [InventoryLocationType::MAIN_STORE]);
        $allowedRequestingLocationIds = $this->accessibleLocationIds($user, $branchId, $normalizedRequestingTypes);

        return in_array($fulfillingLocationId, $allowedFulfillingLocationIds, true)
            && in_array($requestingLocationId, $allowedRequestingLocationIds, true);
    }

    public function canViewRequestedRequisition(?User $user, InventoryRequisition $requisition, ?string $branchId = null): bool
    {
        $locationIds = $this->accessibleLocationIds($user, $branchId);

        return in_array($requisition->source_inventory_location_id, $locationIds, true)
            || in_array($requisition->destination_inventory_location_id, $locationIds, true);
    }

    public function canFulfillRequisition(?User $user, InventoryRequisition $requisition, ?string $branchId = null): bool
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

        $types = [];

        if ($user->hasRole('pharmacist')) {
            $types[] = InventoryLocationType::PHARMACY->value;
        }

        if ($user->hasRole('lab_technician')) {
            $types[] = InventoryLocationType::LABORATORY->value;
        }

        return array_values(array_unique($types));
    }

    /**
     * @param  array<int, InventoryLocationType|string>  $types
     * @return list<string>
     */
    private function normalizeTypes(array $types): array
    {
        $normalized = [];

        foreach ($types as $type) {
            $value = $type instanceof InventoryLocationType
                ? $type->value
                : $type;

            if ($value === '') {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }
}
