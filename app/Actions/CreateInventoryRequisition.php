<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateInventoryRequisition
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items): InventoryRequisition
    {
        return DB::transaction(function () use ($attributes, $items): InventoryRequisition {
            $tenantId = is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : Auth::user()?->tenantId();
            $branchId = is_string($attributes['branch_id'] ?? null)
                ? $attributes['branch_id']
                : BranchContext::getActiveBranchId();

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
                'sourceLocation',
                'destinationLocation',
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
