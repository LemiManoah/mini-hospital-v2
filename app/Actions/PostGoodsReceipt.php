<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PostGoodsReceipt
{
    public function handle(GoodsReceipt $goodsReceipt): GoodsReceipt
    {
        abort_unless(
            $goodsReceipt->status === GoodsReceiptStatus::Draft,
            422,
            'Only draft goods receipts can be posted.',
        );

        return DB::transaction(function () use ($goodsReceipt): GoodsReceipt {
            $goodsReceipt->update([
                'status' => GoodsReceiptStatus::Posted,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($goodsReceipt->items as $receiptItem) {
                $poItem = $receiptItem->purchaseOrderItem;

                $poItem->update([
                    'quantity_received' => (float) $poItem->quantity_received + (float) $receiptItem->quantity_received,
                ]);
            }

            $this->updatePurchaseOrderStatus($goodsReceipt);

            return $goodsReceipt->refresh();
        });
    }

    private function updatePurchaseOrderStatus(GoodsReceipt $goodsReceipt): void
    {
        $purchaseOrder = $goodsReceipt->purchaseOrder;
        $purchaseOrder->load('items');

        $allFullyReceived = $purchaseOrder->items->every(
            fn ($item) => (float) $item->quantity_received >= (float) $item->quantity_ordered,
        );

        $anyReceived = $purchaseOrder->items->contains(
            fn ($item) => (float) $item->quantity_received > 0,
        );

        if ($allFullyReceived) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Received]);
        } elseif ($anyReceived) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Partial]);
        }
    }
}
