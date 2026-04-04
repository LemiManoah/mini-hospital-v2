<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreatePurchaseOrder
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes, $items): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::query()->create([
                ...$attributes,
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
}
