<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\CreateGoodsReceiptDTO;
use App\Data\Inventory\CreateGoodsReceiptItemDTO;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateGoodsReceipt
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public function handle(CreateGoodsReceiptDTO $data): GoodsReceipt
    {
        return DB::transaction(function () use ($data): GoodsReceipt {
            $purchaseOrder = PurchaseOrder::query()
                ->lockForUpdate()
                ->with('items:id,purchase_order_id,inventory_item_id,quantity_ordered,quantity_received')
                ->where('id', $data->purchaseOrderId)
                ->firstOrFail();

            $canAccess = $data->allowedLocationTypes === []
                ? $this->inventoryLocationAccess->canAccessLocation(
                    Auth::user(),
                    $data->inventoryLocationId,
                    BranchContext::getActiveBranchId(),
                )
                : $this->inventoryLocationAccess->canAccessLocationForTypes(
                    Auth::user(),
                    $data->inventoryLocationId,
                    $data->allowedLocationTypes,
                    BranchContext::getActiveBranchId(),
                );

            abort_unless(
                $canAccess,
                403,
                'You can only receive goods into inventory locations you manage.',
            );

            abort_unless(
                in_array($purchaseOrder->status, [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Partial], true),
                422,
                'Goods can only be received against approved or partially received purchase orders.',
            );

            $this->ensureNoDraftGoodsReceiptExists($purchaseOrder);
            $this->validateReceiptItems($purchaseOrder, $data->receiptItems());

            $tenantId = Auth::user()?->tenantId();

            $goodsReceipt = GoodsReceipt::query()->create([
                'purchase_order_id' => $data->purchaseOrderId,
                'inventory_location_id' => $data->inventoryLocationId,
                'receipt_date' => $data->receiptDate,
                'supplier_invoice_number' => $data->supplierInvoiceNumber,
                'notes' => $data->notes,
                'receipt_number' => $this->generateReceiptNumber($tenantId),
                'status' => GoodsReceiptStatus::Draft,
                'created_by' => Auth::id(),
            ]);

            foreach ($data->receiptItems() as $item) {
                $goodsReceipt->items()->create([
                    'purchase_order_item_id' => $item->purchaseOrderItemId,
                    'inventory_item_id' => $item->inventoryItemId,
                    'quantity_received' => $item->quantityReceived,
                    'unit_cost' => $item->unitCost,
                    'batch_number' => $item->batchNumber,
                    'expiry_date' => $item->expiryDate,
                    'notes' => $item->notes,
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
     * @param  list<CreateGoodsReceiptItemDTO>  $items
     */
    private function validateReceiptItems(PurchaseOrder $purchaseOrder, array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Receive at least one item quantity greater than zero before saving the goods receipt.',
            ]);
        }

        /** @var array<string, PurchaseOrderItem> $purchaseOrderItems */
        $purchaseOrderItems = $purchaseOrder->items
            ->keyBy('id')
            ->all();

        $inventoryItems = InventoryItem::query()
            ->whereIn(
                'id',
                array_map(
                    static fn (CreateGoodsReceiptItemDTO $item): string => $item->inventoryItemId,
                    $items,
                ),
            )
            ->get(['id', 'expires'])
            ->keyBy('id');

        foreach ($items as $index => $item) {
            $purchaseOrderItem = $purchaseOrderItems[$item->purchaseOrderItemId] ?? null;

            abort_unless(
                $purchaseOrderItem instanceof PurchaseOrderItem,
                422,
                sprintf('Receipt item %d does not belong to the selected purchase order.', $index + 1),
            );

            abort_unless(
                $purchaseOrderItem->inventory_item_id === $item->inventoryItemId,
                422,
                sprintf('Receipt item %d does not match the selected purchase order item inventory.', $index + 1),
            );

            $remainingQuantity = (float) $purchaseOrderItem->quantity_ordered - (float) $purchaseOrderItem->quantity_received;

            if ($item->quantityReceived > $remainingQuantity) {
                throw ValidationException::withMessages([
                    sprintf('items.%d.quantity_received', $index) => 'The received quantity cannot be greater than the remaining purchase order quantity.',
                ]);
            }

            $inventoryItem = $inventoryItems->get($item->inventoryItemId);

            if (
                $inventoryItem instanceof InventoryItem
                && $inventoryItem->expires
                && $item->quantityReceived > 0
            ) {
                if ($item->batchNumber === null) {
                    throw ValidationException::withMessages([
                        sprintf('items.%d.batch_number', $index) => 'Batch number is required for expirable items.',
                    ]);
                }

                if ($item->expiryDate === null) {
                    throw ValidationException::withMessages([
                        sprintf('items.%d.expiry_date', $index) => 'Expiry date is required for expirable items.',
                    ]);
                }
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
