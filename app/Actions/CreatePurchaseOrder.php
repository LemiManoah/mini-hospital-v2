<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreatePurchaseOrder
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
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
                $purchaseOrder->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => round((float) $item['quantity_ordered'] * (float) $item['unit_cost'], 2),
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
