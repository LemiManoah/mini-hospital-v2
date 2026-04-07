<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateGoodsReceipt;
use App\Actions\PostGoodsReceipt;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GoodsReceiptController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

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
        $workspace = InventoryWorkspace::fromRequest($request);
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));
        $locationIds = $this->inventoryLocationAccess->accessibleLocationIds(
            Auth::user(),
            BranchContext::getActiveBranchId(),
            $workspace->locationTypeValues(),
        );

        $goodsReceipts = GoodsReceipt::query()
            ->with(['purchaseOrder.supplier:id,name', 'inventoryLocation:id,name'])
            ->when(
                $locationIds === [],
                static fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                static fn (Builder $query): Builder => $query->whereIn('inventory_location_id', $locationIds),
            )
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

        return Inertia::render($workspace->goodsReceiptIndexComponent(), [
            'goodsReceipts' => $goodsReceipts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        $purchaseOrderId = $request->query('purchase_order_id');
        $branchId = BranchContext::getActiveBranchId();

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

        return Inertia::render($workspace->goodsReceiptCreateComponent(), [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'purchaseOrders' => $purchaseOrders,
            'selectedPurchaseOrder' => $selectedPurchaseOrder,
            'inventoryLocations' => $this->inventoryLocationAccess
                ->accessibleLocations(Auth::user(), $branchId, $workspace->locationTypeValues())
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                ])
                ->values(),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request, CreateGoodsReceipt $action): RedirectResponse
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $goodsReceipt = $action->handle($validated, $items, $workspace->locationTypeValues());

        return to_route($workspace->goodsReceiptShowRouteName(), $workspace->goodsReceiptShowRouteParameters($goodsReceipt))
            ->with('success', 'Goods receipt created successfully.');
    }

    public function show(Request $request, GoodsReceipt $goodsReceipt): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless(
            $this->inventoryLocationAccess->canAccessLocation(Auth::user(), $goodsReceipt->inventory_location_id, $goodsReceipt->branch_id),
            403,
            'You do not have access to this goods receipt.',
        );

        abort_unless(
            $workspace->locationTypeValues() === []
                || $this->inventoryLocationAccess->canAccessLocationForTypes(
                    Auth::user(),
                    $goodsReceipt->inventory_location_id,
                    $workspace->locationTypeValues(),
                    $goodsReceipt->branch_id,
                ),
            404,
            'This goods receipt does not belong to the selected inventory workspace.',
        );

        $goodsReceipt->load(['purchaseOrder.supplier', 'inventoryLocation', 'items.inventoryItem', 'items.purchaseOrderItem']);

        return Inertia::render($workspace->goodsReceiptShowComponent(), [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'goodsReceipt' => $goodsReceipt,
        ]);
    }

    public function post(Request $request, GoodsReceipt $goodsReceipt, PostGoodsReceipt $action): RedirectResponse
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless(
            $this->inventoryLocationAccess->canAccessLocation(Auth::user(), $goodsReceipt->inventory_location_id, $goodsReceipt->branch_id),
            403,
            'You do not have access to this goods receipt.',
        );

        $action->handle($goodsReceipt->load('items.purchaseOrderItem'));

        return to_route($workspace->goodsReceiptShowRouteName(), $workspace->goodsReceiptShowRouteParameters($goodsReceipt))
            ->with('success', 'Goods receipt posted. Stock quantities updated.');
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
