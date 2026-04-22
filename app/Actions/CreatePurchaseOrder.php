<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreatePurchaseOrder
{
    /**
     * @param  array{
     *      tenant_id?: string,
     *      branch_id: string,
     *      supplier_id: string,
     *      order_date: string,
     *      expected_delivery_date?: string|null,
     *      notes?: string|null,
     *      approved_by?: string|null,
     *      approved_at?: string|null,
     *      updated_by?: string|null,
     *      total_amount?: float|int|string
     *  }  $attributes
     * @param  list<array{
     *      inventory_item_id: string,
     *      quantity_ordered: float|int|string,
     *      unit_cost: float|int|string
     *  }>  $items
     */
    public function handle(array $attributes, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes, $items): PurchaseOrder {
            $tenantId = is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : Auth::user()?->tenantId();

            $purchaseOrder = PurchaseOrder::query()->create([
                ...$attributes,
                'order_number' => $this->generateOrderNumber($tenantId),
                'status' => PurchaseOrderStatus::Draft,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $quantityOrdered = (float) $item['quantity_ordered'];
                $unitCost = (float) $item['unit_cost'];

                $purchaseOrder->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_ordered' => $quantityOrdered,
                    'unit_cost' => $unitCost,
                    'total_cost' => round($quantityOrdered * $unitCost, 2),
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
