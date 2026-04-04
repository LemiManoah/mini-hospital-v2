<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateGoodsReceipt
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items): GoodsReceipt
    {
        $purchaseOrder = PurchaseOrder::query()->findOrFail($attributes['purchase_order_id']);

        abort_unless(
            in_array($purchaseOrder->status, [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Partial], true),
            422,
            'Goods can only be received against approved or partially received purchase orders.',
        );

        return DB::transaction(function () use ($attributes, $items): GoodsReceipt {
            $goodsReceipt = GoodsReceipt::query()->create([
                ...$attributes,
                'status' => GoodsReceiptStatus::Draft,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $goodsReceipt->items()->create([
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_received' => $item['quantity_received'],
                    'unit_cost' => $item['unit_cost'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $goodsReceipt->refresh()->load('items.inventoryItem');
        });
    }
}
