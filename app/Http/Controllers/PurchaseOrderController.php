<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApprovePurchaseOrder;
use App\Actions\CancelPurchaseOrder;
use App\Actions\CreatePurchaseOrder;
use App\Actions\SubmitPurchaseOrder;
use App\Actions\UpdatePurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PurchaseOrderController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchase_orders.view', only: ['index', 'show']),
            new Middleware('permission:purchase_orders.create', only: ['create', 'store']),
            new Middleware('permission:purchase_orders.update', only: ['edit', 'update', 'submit', 'approve', 'cancel']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,name'])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('order_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('supplier', static fn (Builder $q) => $q->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->latest('order_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory/purchase-orders/create', [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'inventoryItems' => InventoryItem::query()->active()->orderBy('name')->get(['id', 'name', 'generic_name', 'item_type']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, CreatePurchaseOrder $action): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $purchaseOrder = $action->handle($validated, $items);

        return to_route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'items.inventoryItem', 'goodsReceipts.items']);

        return Inertia::render('inventory/purchase-orders/show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_unless(
            $purchaseOrder->status === PurchaseOrderStatus::Draft,
            422,
            'Only draft purchase orders can be edited.',
        );

        $purchaseOrder->load('items.inventoryItem');

        return Inertia::render('inventory/purchase-orders/edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'inventoryItems' => InventoryItem::query()->active()->orderBy('name')->get(['id', 'name', 'generic_name', 'item_type']),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, UpdatePurchaseOrder $action): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $action->handle($purchaseOrder, $validated, $items);

        return to_route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order updated successfully.');
    }

    public function submit(PurchaseOrder $purchaseOrder, SubmitPurchaseOrder $action): RedirectResponse
    {
        $action->handle($purchaseOrder);

        return to_route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order submitted for approval.');
    }

    public function approve(PurchaseOrder $purchaseOrder, ApprovePurchaseOrder $action): RedirectResponse
    {
        $action->handle($purchaseOrder);

        return to_route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order approved.');
    }

    public function cancel(PurchaseOrder $purchaseOrder, CancelPurchaseOrder $action): RedirectResponse
    {
        $action->handle($purchaseOrder);

        return to_route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order cancelled.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(PurchaseOrderStatus::cases())
            ->map(static fn (PurchaseOrderStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }
}
