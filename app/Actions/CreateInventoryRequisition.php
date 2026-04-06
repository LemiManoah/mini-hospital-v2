<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateInventoryRequisition
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items, array $destinationTypes = []): InventoryRequisition
    {
        return DB::transaction(function () use ($attributes, $items, $destinationTypes): InventoryRequisition {
            $tenantId = is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : Auth::user()?->tenantId();
            $branchId = is_string($attributes['branch_id'] ?? null)
                ? $attributes['branch_id']
                : BranchContext::getActiveBranchId();

            $fulfillingLocationId = is_string($attributes['source_inventory_location_id'] ?? null)
                ? $attributes['source_inventory_location_id']
                : null;
            $requestingLocationId = is_string($attributes['destination_inventory_location_id'] ?? null)
                ? $attributes['destination_inventory_location_id']
                : null;

            $canCreate = $this->inventoryLocationAccess->canCreateRequestedRequisition(
                Auth::user(),
                $fulfillingLocationId,
                $requestingLocationId,
                $destinationTypes,
                is_string($branchId) ? $branchId : null,
            );

            abort_unless(
                $canCreate,
                403,
                'You can only create requisitions for inventory locations you manage.',
            );

            $requisition = InventoryRequisition::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'source_inventory_location_id' => $attributes['source_inventory_location_id'],
                'destination_inventory_location_id' => $attributes['destination_inventory_location_id'],
                'requisition_number' => $this->generateRequisitionNumber($tenantId),
                'status' => InventoryRequisitionStatus::Draft,
                'priority' => $attributes['priority'],
                'requisition_date' => $attributes['requisition_date'],
                'notes' => ($attributes['notes'] ?? '') !== '' ? $attributes['notes'] : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $requisition->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'approved_quantity' => 0,
                    'issued_quantity' => 0,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                ]);
            }

            return $requisition->refresh()->load([
                'fulfillingLocation',
                'requestingLocation',
                'items.inventoryItem',
            ]);
        });
    }

    private function generateRequisitionNumber(?string $tenantId): string
    {
        do {
            $requisitionNumber = 'REQ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && InventoryRequisition::query()
                ->where('tenant_id', $tenantId)
                ->where('requisition_number', $requisitionNumber)
                ->exists()
        );

        return $requisitionNumber;
    }
}
