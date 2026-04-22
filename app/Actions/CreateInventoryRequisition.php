<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryLocationType;
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
     * @param  array{
     *      tenant_id?: string,
     *      branch_id?: string,
     *      source_inventory_location_id: string,
     *      destination_inventory_location_id: string,
     *      priority: string,
     *      requisition_date: string,
     *      notes?: string|null
     *  }  $attributes
     * @param  list<array{
     *      inventory_item_id: string,
     *      requested_quantity: float|int|string,
     *      notes?: string|null
     *  }>  $items
     * @param  list<InventoryLocationType|string>  $destinationTypes
     */
    public function handle(array $attributes, array $items, array $destinationTypes = []): InventoryRequisition
    {
        return DB::transaction(function () use ($attributes, $items, $destinationTypes): InventoryRequisition {
            $tenantId = $attributes['tenant_id'] ?? Auth::user()?->tenantId();
            $branchId = $attributes['branch_id'] ?? BranchContext::getActiveBranchId();

            $fulfillingLocationId = $attributes['source_inventory_location_id'];
            $requestingLocationId = $attributes['destination_inventory_location_id'];

            $normalizedDestinationTypes = $destinationTypes;

            $canCreate = $this->inventoryLocationAccess->canCreateRequestedRequisition(
                Auth::user(),
                $fulfillingLocationId,
                $requestingLocationId,
                $normalizedDestinationTypes,
                $branchId,
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
