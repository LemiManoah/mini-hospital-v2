<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStockAdjustment;
use App\Actions\PostStockAdjustment;
use App\Enums\StockAdjustmentStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockAdjustment;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\StoreStockAdjustmentRequest;

final readonly class StockAdjustmentController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock_adjustments.view', only: ['index', 'show']),
            new Middleware('permission:stock_adjustments.create', only: ['create', 'store']),
            new Middleware('permission:stock_adjustments.update', only: ['post']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $stockAdjustments = StockAdjustment::query()
            ->with('inventoryLocation:id,name,location_code')
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where(function (Builder $inner) use ($search): void {
                        $inner
                            ->where('adjustment_number', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('reason', 'like', sprintf('%%%s%%', $search));
                    });
                },
            )
            ->when(
                $status !== '',
                static fn (Builder $query) => $query->where('status', $status),
            )
            ->latest('adjustment_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/adjustments/index', [
            'stockAdjustments' => $stockAdjustments,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        $locationBalances = is_string($activeBranchId) && $activeBranchId !== ''
            ? $this->inventoryStockLedger
                ->summarizeByLocation($activeBranchId)
                ->values()
            : collect();

        $batchBalances = is_string($activeBranchId) && $activeBranchId !== ''
            ? $this->inventoryStockLedger
                ->summarizeByBatch($activeBranchId)
                ->filter(static fn (array $balance): bool => $balance['quantity'] > 0)
                ->values()
            : collect();

        /** @var array<string, InventoryBatch> $batches */
        $batches = InventoryBatch::query()
            ->with([
                'inventoryItem:id,name,generic_name',
                'inventoryLocation:id,name,location_code',
            ])
            ->whereIn('id', $batchBalances->pluck('inventory_batch_id'))
            ->get()
            ->keyBy('id')
            ->all();

        return Inertia::render('inventory/adjustments/create', [
            'inventoryLocations' => InventoryLocation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location_code']),
            'inventoryItems' => InventoryItem::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'item_type', 'default_purchase_price']),
            'locationBalances' => $locationBalances->map(static fn (array $balance): array => [
                'inventory_location_id' => $balance['inventory_location_id'],
                'inventory_item_id' => $balance['inventory_item_id'],
                'quantity' => $balance['quantity'],
            ])->values(),
            'batchBalances' => $batchBalances->map(static function (array $balance) use ($batches): array {
                $batch = $batches[$balance['inventory_batch_id']] ?? null;

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_location_id' => $balance['inventory_location_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'item_name' => $batch?->inventoryItem?->generic_name ?? $batch?->inventoryItem?->name,
                    'location_name' => $batch?->inventoryLocation?->name,
                ];
            })->values(),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request, CreateStockAdjustment $action): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $stockAdjustment = $action->handle($validated, $items);

        return to_route('stock-adjustments.show', $stockAdjustment)
            ->with('success', 'Stock adjustment created successfully.');
    }

    public function show(StockAdjustment $stockAdjustment): Response
    {
        $stockAdjustment->load([
            'inventoryLocation',
            'items.inventoryItem',
            'items.inventoryBatch',
        ]);

        return Inertia::render('inventory/adjustments/show', [
            'stockAdjustment' => $stockAdjustment,
        ]);
    }

    public function post(StockAdjustment $stockAdjustment, PostStockAdjustment $action): RedirectResponse
    {
        $action->handle($stockAdjustment->load('items.inventoryBatch'));

        return to_route('stock-adjustments.show', $stockAdjustment)
            ->with('success', 'Stock adjustment posted. Inventory balances updated.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(StockAdjustmentStatus::cases())
            ->map(static fn (StockAdjustmentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }
}
