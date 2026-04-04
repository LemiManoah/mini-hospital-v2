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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateGoodsReceipt
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items): GoodsReceipt
    {
        return DB::transaction(function () use ($attributes, $items): GoodsReceipt {
            $purchaseOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->with('items:id,purchase_order_id,inventory_item_id,quantity_ordered,quantity_received')
                ->findOrFail($attributes['purchase_order_id']);

            abort_unless(
                in_array($purchaseOrder->status, [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Partial], true),
                422,
                'Goods can only be received against approved or partially received purchase orders.',
            );

            $this->ensureNoDraftGoodsReceiptExists($purchaseOrder);
            $this->validateReceiptItems($purchaseOrder, $items);

            $tenantId = is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : Auth::user()?->tenantId();

            $goodsReceipt = GoodsReceipt::query()->create([
                ...$attributes,
                'receipt_number' => $this->generateReceiptNumber($tenantId),
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

    private function ensureNoDraftGoodsReceiptExists(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->goodsReceipts()->where('status', GoodsReceiptStatus::Draft)->exists()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'This purchase order already has a draft goods receipt. Post that receipt before creating another one.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function validateReceiptItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        /** @var array<string, PurchaseOrderItem> $purchaseOrderItems */
        $purchaseOrderItems = $purchaseOrder->items
            ->keyBy('id')
            ->all();

        foreach ($items as $index => $item) {
            $purchaseOrderItemId = $item['purchase_order_item_id'] ?? null;
            $inventoryItemId = $item['inventory_item_id'] ?? null;
            $purchaseOrderItem = is_string($purchaseOrderItemId)
                ? ($purchaseOrderItems[$purchaseOrderItemId] ?? null)
                : null;

            abort_unless(
                $purchaseOrderItem instanceof PurchaseOrderItem,
                422,
                sprintf('Receipt item %d does not belong to the selected purchase order.', $index + 1),
            );

            abort_unless(
                is_string($inventoryItemId) && $purchaseOrderItem->inventory_item_id === $inventoryItemId,
                422,
                sprintf('Receipt item %d does not match the selected purchase order item inventory.', $index + 1),
            );

            $quantityReceived = $item['quantity_received'] ?? null;
            $remainingQuantity = (float) $purchaseOrderItem->quantity_ordered - (float) $purchaseOrderItem->quantity_received;

            if (! is_numeric($quantityReceived) || (float) $quantityReceived > $remainingQuantity) {
                throw ValidationException::withMessages([
                    sprintf('items.%d.quantity_received', $index) => 'The received quantity cannot be greater than the remaining purchase order quantity.',
                ]);
            }
        }
    }

    private function generateReceiptNumber(?string $tenantId): string
    {
        do {
            $receiptNumber = 'GR-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && GoodsReceipt::query()->where('tenant_id', $tenantId)->where('receipt_number', $receiptNumber)->exists()
        );

        return $receiptNumber;
    }
}
