<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateGoodsReceipt;
use App\Actions\PostGoodsReceipt;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\StoreGoodsReceiptRequest;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GoodsReceiptController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:goods_receipts.view', only: ['index', 'show']),
            new Middleware('permission:goods_receipts.create', only: ['create', 'store']),
            new Middleware('permission:goods_receipts.update', only: ['post']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $goodsReceipts = GoodsReceipt::query()
            ->with(['purchaseOrder.supplier:id,name', 'inventoryLocation:id,name'])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('receipt_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('supplier_invoice_number', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->latest('receipt_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/goods-receipts/index', [
            'goodsReceipts' => $goodsReceipts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        $purchaseOrderId = $request->query('purchase_order_id');

        $receivableStatuses = [PurchaseOrderStatus::Approved->value, PurchaseOrderStatus::Partial->value];

        $purchaseOrders = PurchaseOrder::query()
            ->with([
                'supplier:id,name',
                'items.inventoryItem:id,name,generic_name',
                'goodsReceipts:id,purchase_order_id,receipt_number,status,receipt_date',
            ])
            ->whereIn('status', $receivableStatuses)
            ->orderBy('order_number')
            ->get();

        $selectedPurchaseOrder = $purchaseOrderId
            ? $purchaseOrders->firstWhere('id', $purchaseOrderId)
            : null;

        return Inertia::render('inventory/goods-receipts/create', [
            'purchaseOrders' => $purchaseOrders,
            'selectedPurchaseOrder' => $selectedPurchaseOrder,
            'inventoryLocations' => InventoryLocation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location_code']),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request, CreateGoodsReceipt $action): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $goodsReceipt = $action->handle($validated, $items);

        return to_route('goods-receipts.show', $goodsReceipt)->with('success', 'Goods receipt created successfully.');
    }

    public function show(GoodsReceipt $goodsReceipt): Response
    {
        $goodsReceipt->load(['purchaseOrder.supplier', 'inventoryLocation', 'items.inventoryItem', 'items.purchaseOrderItem']);

        return Inertia::render('inventory/goods-receipts/show', [
            'goodsReceipt' => $goodsReceipt,
        ]);
    }

    public function post(GoodsReceipt $goodsReceipt, PostGoodsReceipt $action): RedirectResponse
    {
        $action->handle($goodsReceipt->load('items.purchaseOrderItem'));

        return to_route('goods-receipts.show', $goodsReceipt)->with('success', 'Goods receipt posted. Stock quantities updated.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(GoodsReceiptStatus::cases())
            ->map(static fn (GoodsReceiptStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }
}
