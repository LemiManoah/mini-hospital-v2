<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\CreatePurchaseOrderDTO;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreatePurchaseOrder
{
    public function handle(CreatePurchaseOrderDTO $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data): PurchaseOrder {
            $tenantId = Auth::user()?->tenantId();

            $purchaseOrder = PurchaseOrder::query()->create([
                'supplier_id' => $data->supplierId,
                'order_date' => $data->orderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'notes' => $data->notes,
                'order_number' => $this->generateOrderNumber($tenantId),
                'status' => PurchaseOrderStatus::Draft,
                'created_by' => Auth::id(),
            ]);

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

    private function generateOrderNumber(?string $tenantId): string
    {
        do {
            $orderNumber = 'PO-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && PurchaseOrder::query()->where('tenant_id', $tenantId)->where('order_number', $orderNumber)->exists()
        );

        return $orderNumber;
    }
}
