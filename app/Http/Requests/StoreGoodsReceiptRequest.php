<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Inventory\CreateGoodsReceiptDTO;
use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrderItem;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryWorkspace;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreGoodsReceiptRequest extends FormRequest
{
    /**
     * @param  list<string>  $allowedLocationTypes
     */
    public function createDto(array $allowedLocationTypes = []): CreateGoodsReceiptDTO
    {
        return CreateGoodsReceiptDTO::fromRequest($this, $allowedLocationTypes);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => [
                'required',
                'string',
                Rule::exists('purchase_orders', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'inventory_location_id' => [
                'required',
                'string',
                Rule::exists('inventory_locations', 'id')
                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereNull('deleted_at')),
            ],
            'receipt_date' => ['required', 'date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'string', 'distinct'],
            'items.*.inventory_item_id' => ['required', 'string'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $purchaseOrderId = $this->input('purchase_order_id');
                $inventoryLocationId = $this->input('inventory_location_id');
                $items = $this->input('items');
                $activeBranchId = BranchContext::getActiveBranchId();
                $workspace = InventoryWorkspace::fromRequest($this);
                $workspaceTypes = $workspace->locationTypeValues();

                if (
                    ! is_string($purchaseOrderId)
                    || ! is_string($inventoryLocationId)
                    || ! is_array($items)
                    || $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $inventoryLocationAccess = resolve(InventoryLocationAccess::class);

                $canAccessLocation = $workspaceTypes === []
                    ? $inventoryLocationAccess->canAccessLocation($this->user(), $inventoryLocationId, $activeBranchId)
                    : $inventoryLocationAccess->canAccessLocationForTypes(
                        $this->user(),
                        $inventoryLocationId,
                        $workspaceTypes,
                        $activeBranchId,
                    );

                if (! $canAccessLocation) {
                    $validator->errors()->add(
                        'inventory_location_id',
                        'You can only receive goods into inventory locations you manage.',
                    );

                    return;
                }

                if ($workspaceTypes !== []) {
                    $location = InventoryLocation::query()
                        ->where('id', $inventoryLocationId)
                        ->where('branch_id', $activeBranchId)
                        ->first();

                    if (
                        ! $location instanceof InventoryLocation
                        || ! in_array($location->type->value, $workspaceTypes, true)
                    ) {
                        $validator->errors()->add(
                            'inventory_location_id',
                            'The receiving location does not belong to this workspace.',
                        );

                        return;
                    }
                }

                if (GoodsReceipt::query()
                    ->where('purchase_order_id', $purchaseOrderId)
                    ->where('status', GoodsReceiptStatus::Draft)
                    ->exists()
                ) {
                    $validator->errors()->add(
                        'purchase_order_id',
                        'This purchase order already has a draft goods receipt. Post that receipt before creating another one.',
                    );

                    return;
                }

                $purchaseOrderItemIds = [];

                foreach ($items as $item) {
                    if (
                        is_array($item)
                        && is_string($item['purchase_order_item_id'] ?? null)
                        && $item['purchase_order_item_id'] !== ''
                    ) {
                        $purchaseOrderItemIds[] = $item['purchase_order_item_id'];
                    }
                }

                if ($purchaseOrderItemIds === []) {
                    return;
                }

                /** @var array<string, PurchaseOrderItem> $purchaseOrderItems */
                $purchaseOrderItems = PurchaseOrderItem::query()
                    ->whereIn('id', $purchaseOrderItemIds)
                    ->get(['id', 'purchase_order_id', 'inventory_item_id', 'quantity_ordered', 'quantity_received'])
                    ->keyBy('id')
                    ->all();

                $inventoryItems = InventoryItem::query()
                    ->whereIn(
                        'id',
                        collect($items)
                            ->pluck('inventory_item_id')
                            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
                            ->values()
                            ->all(),
                    )
                    ->get(['id', 'expires'])
                    ->keyBy('id');

                $positiveQuantityCount = 0;

                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $purchaseOrderItemId = $item['purchase_order_item_id'] ?? null;
                    $inventoryItemId = $item['inventory_item_id'] ?? null;
                    if (! is_string($purchaseOrderItemId)) {
                        continue;
                    }

                    if ($purchaseOrderItemId === '') {
                        continue;
                    }

                    $purchaseOrderItem = $purchaseOrderItems[$purchaseOrderItemId] ?? null;

                    if (! $purchaseOrderItem instanceof PurchaseOrderItem) {
                        continue;
                    }

                    if ($purchaseOrderItem->purchase_order_id !== $purchaseOrderId) {
                        $validator->errors()->add(
                            sprintf('items.%s.purchase_order_item_id', $index),
                            'The selected purchase order item does not belong to the selected purchase order.',
                        );
                    }

                    if (
                        is_string($inventoryItemId)
                        && $inventoryItemId !== ''
                        && $purchaseOrderItem->inventory_item_id !== $inventoryItemId
                    ) {
                        $validator->errors()->add(
                            sprintf('items.%s.inventory_item_id', $index),
                            'The selected inventory item does not match the purchase order item.',
                        );
                    }

                    $submittedQuantity = $item['quantity_received'] ?? null;

                    if (is_numeric($submittedQuantity)) {
                        if ((float) $submittedQuantity > 0) {
                            $positiveQuantityCount++;
                        }

                        $remainingQuantity = (float) $purchaseOrderItem->quantity_ordered - (float) $purchaseOrderItem->quantity_received;

                        if ((float) $submittedQuantity > $remainingQuantity) {
                            $validator->errors()->add(
                                sprintf('items.%s.quantity_received', $index),
                                'The received quantity cannot be greater than the remaining purchase order quantity.',
                            );
                        }
                    }

                    $inventoryItem = is_string($inventoryItemId)
                        ? $inventoryItems->get($inventoryItemId)
                        : null;

                    if (
                        $inventoryItem instanceof InventoryItem
                        && $inventoryItem->expires
                        && is_numeric($submittedQuantity)
                        && (float) $submittedQuantity > 0
                    ) {
                        if (! is_string($item['batch_number'] ?? null) || mb_trim($item['batch_number']) === '') {
                            $validator->errors()->add(
                                sprintf('items.%s.batch_number', $index),
                                'Batch number is required for expirable items.',
                            );
                        }

                        if (! is_string($item['expiry_date'] ?? null) || mb_trim($item['expiry_date']) === '') {
                            $validator->errors()->add(
                                sprintf('items.%s.expiry_date', $index),
                                'Expiry date is required for expirable items.',
                            );
                        }
                    }
                }

                if ($positiveQuantityCount === 0) {
                    $validator->errors()->add(
                        'items',
                        'Receive at least one item quantity greater than zero before saving the goods receipt.',
                    );
                }
            },
        ];
    }
}
