<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\UpdatePurchaseOrderDTO;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePurchaseOrder
{
    public function handle(PurchaseOrder $purchaseOrder, UpdatePurchaseOrderDTO $data): PurchaseOrder
    {
        abort_unless(
            $purchaseOrder->status === PurchaseOrderStatus::Draft,
            422,
            'Only draft purchase orders can be edited.',
        );

        return DB::transaction(function () use ($purchaseOrder, $data): PurchaseOrder {
            $purchaseOrder->update([
                'supplier_id' => $data->supplierId,
                'order_date' => $data->orderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'notes' => $data->notes,
                'updated_by' => Auth::id(),
            ]);

            $purchaseOrder->items()->delete();

            foreach ($data->items as $item) {
                $purchaseOrder->items()->create([
                    'inventory_item_id' => $item->inventoryItemId,
                    'quantity_ordered' => $item->quantityOrdered,
                    'unit_cost' => $item->unitCost,
                    'total_cost' => round($item->quantityOrdered * $item->unitCost, 2),
                ]);
            }

            $purchaseOrder->recalculateTotal();

            return $purchaseOrder->refresh()->load('items.inventoryItem');
        });
    }
}
