<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PostGoodsReceipt
{
    public function handle(GoodsReceipt $goodsReceipt): GoodsReceipt
    {
        return DB::transaction(function () use ($goodsReceipt): GoodsReceipt {
            $purchaseOrder = $goodsReceipt->purchaseOrder()
                ->lockForUpdate()
                ->firstOrFail();

            $updatedRows = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->where('status', GoodsReceiptStatus::Draft)
                ->update([
                'status' => GoodsReceiptStatus::Posted,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'updated_by' => Auth::id(),
                ]);

            abort_unless(
                $updatedRows === 1,
                422,
                'Only draft goods receipts can be posted.',
            );

            $goodsReceipt = GoodsReceipt::query()
                ->with('items')
                ->findOrFail($goodsReceipt->id);

            foreach ($goodsReceipt->items as $receiptItem) {
                PurchaseOrderItem::query()
                    ->whereKey($receiptItem->purchase_order_item_id)
                    ->increment('quantity_received', (float) $receiptItem->quantity_received);
            }

            $this->updatePurchaseOrderStatus($purchaseOrder);

            return $goodsReceipt->refresh()->load('items.purchaseOrderItem');
        });
    }

    private function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('items');

        $allFullyReceived = $purchaseOrder->items->every(
            static fn (PurchaseOrderItem $item): bool => (float) $item->quantity_received >= (float) $item->quantity_ordered,
        );

        $anyReceived = $purchaseOrder->items->contains(
            static fn (PurchaseOrderItem $item): bool => (float) $item->quantity_received > 0,
        );

        if ($allFullyReceived) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Received]);
        } elseif ($anyReceived) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Partial]);
        }
    }
}
