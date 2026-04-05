<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStockCount;
use App\Actions\PostStockCount;
use App\Enums\StockCountStatus;
use App\Http\Requests\StoreStockCountRequest;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockCount;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StockCountController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock_counts.view', only: ['index', 'show']),
            new Middleware('permission:stock_counts.create', only: ['create', 'store']),
            new Middleware('permission:stock_counts.update', only: ['post']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $stockCounts = StockCount::query()
            ->with('inventoryLocation:id,name,location_code')
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where('count_number', 'like', sprintf('%%%s%%', $search));
                },
            )
            ->when(
                $status !== '',
                static fn (Builder $query) => $query->where('status', $status),
            )
            ->latest('count_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/counts/index', [
            'stockCounts' => $stockCounts,
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

        return Inertia::render('inventory/counts/create', [
            'inventoryLocations' => InventoryLocation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location_code']),
            'inventoryItems' => InventoryItem::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'item_type']),
            'locationBalances' => $locationBalances->map(static fn (array $balance): array => [
                'inventory_location_id' => $balance['inventory_location_id'],
                'inventory_item_id' => $balance['inventory_item_id'],
                'quantity' => $balance['quantity'],
            ])->values(),
        ]);
    }

    public function store(StoreStockCountRequest $request, CreateStockCount $action): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $stockCount = $action->handle($validated, $items);

        return to_route('stock-counts.show', $stockCount)
            ->with('success', 'Stock count created successfully.');
    }

    public function show(StockCount $stockCount): Response
    {
        $stockCount->load([
            'inventoryLocation',
            'items.inventoryItem',
        ]);

        return Inertia::render('inventory/counts/show', [
            'stockCount' => $stockCount,
        ]);
    }

    public function post(StockCount $stockCount, PostStockCount $action): RedirectResponse
    {
        $action->handle($stockCount);

        return to_route('stock-counts.show', $stockCount)
            ->with('success', 'Stock count posted. Inventory balances updated.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return collect(StockCountStatus::cases())
            ->map(static fn (StockCountStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }
}
