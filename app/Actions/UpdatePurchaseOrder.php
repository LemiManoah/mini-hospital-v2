<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePurchaseOrder
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(PurchaseOrder $purchaseOrder, array $attributes, array $items): PurchaseOrder
    {
        abort_unless(
            $purchaseOrder->status === PurchaseOrderStatus::Draft,
            422,
            'Only draft purchase orders can be edited.',
        );

        return DB::transaction(function () use ($purchaseOrder, $attributes, $items): PurchaseOrder {
            $purchaseOrder->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            $purchaseOrder->items()->delete();

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
