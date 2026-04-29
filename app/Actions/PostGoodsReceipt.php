<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PostGoodsReceipt
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(GoodsReceipt $goodsReceipt): GoodsReceipt
    {
        return DB::transaction(function () use ($goodsReceipt): GoodsReceipt {
            $purchaseOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($goodsReceipt->purchase_order_id);

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
                ->findOrFail($goodsReceipt->id);

            /** @var Collection<int, GoodsReceiptItem> $receiptItems */
            $receiptItems = $goodsReceipt->items()->get();

            foreach ($receiptItems as $receiptItem) {
                PurchaseOrderItem::query()
                    ->whereKey($receiptItem->purchase_order_item_id)
                    ->increment('quantity_received', (float) $receiptItem->quantity_received);

                $this->recordStockReceipt($goodsReceipt, $receiptItem);
            }

            $this->updatePurchaseOrderStatus($purchaseOrder);

            $this->recordAuditActivity->handle(
                logName: 'inventory',
                event: 'inventory.goods_receipt.posted',
                subject: $goodsReceipt,
                description: 'Goods receipt posted.',
                tenantId: $goodsReceipt->tenant_id,
                branchId: $goodsReceipt->branch_id,
                staffId: Auth::user()?->staff_id,
                newValues: [
                    'goods_receipt_id' => $goodsReceipt->id,
                    'status' => $goodsReceipt->status->value,
                    'posted_by' => $goodsReceipt->posted_by,
                    'posted_at' => $goodsReceipt->posted_at?->toISOString(),
                    'item_count' => $receiptItems->count(),
                    'quantity_received_total' => round($receiptItems->sum(
                        static fn (GoodsReceiptItem $item): float => (float) $item->quantity_received
                    ), 3),
                ],
                metadata: [
                    'inventory_location_id' => $goodsReceipt->inventory_location_id,
                    'purchase_order_id' => $goodsReceipt->purchase_order_id,
                ],
            );

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

    private function recordStockReceipt(GoodsReceipt $goodsReceipt, GoodsReceiptItem $receiptItem): void
    {
        $batch = InventoryBatch::query()->create([
            'tenant_id' => $goodsReceipt->tenant_id,
            'branch_id' => $goodsReceipt->branch_id,
            'inventory_location_id' => $goodsReceipt->inventory_location_id,
            'inventory_item_id' => $receiptItem->inventory_item_id,
            'goods_receipt_item_id' => $receiptItem->id,
            'batch_number' => $receiptItem->batch_number,
            'expiry_date' => $receiptItem->expiry_date,
            'unit_cost' => $receiptItem->unit_cost,
            'quantity_received' => $receiptItem->quantity_received,
            'received_at' => $goodsReceipt->posted_at ?? now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        StockMovement::query()->create([
            'tenant_id' => $goodsReceipt->tenant_id,
            'branch_id' => $goodsReceipt->branch_id,
            'inventory_location_id' => $goodsReceipt->inventory_location_id,
            'inventory_item_id' => $receiptItem->inventory_item_id,
            'inventory_batch_id' => $batch->id,
            'movement_type' => StockMovementType::Receipt,
            'quantity' => $receiptItem->quantity_received,
            'unit_cost' => $receiptItem->unit_cost,
            'source_document_type' => GoodsReceipt::class,
            'source_document_id' => $goodsReceipt->id,
            'source_line_type' => GoodsReceiptItem::class,
            'source_line_id' => $receiptItem->id,
            'notes' => $receiptItem->notes,
            'occurred_at' => $goodsReceipt->posted_at ?? now(),
            'created_by' => Auth::id(),
        ]);
    }
}
