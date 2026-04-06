<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryDashboardController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(): Response
    {
        $activeBranchId = BranchContext::getActiveBranchId();
        $accessibleLocations = $this->inventoryLocationAccess->accessibleLocations(Auth::user(), $activeBranchId);
        $locationIds = $accessibleLocations
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        /** @var Collection<int, StockMovement> $stockLevels */
        $stockLevels = is_string($activeBranchId) && $activeBranchId !== '' && $locationIds !== []
            ? StockMovement::query()
                ->where('branch_id', $activeBranchId)
                ->whereIn('inventory_location_id', $locationIds)
                ->select('inventory_item_id')
                ->selectRaw('SUM(quantity) as total_qty')
                ->groupBy('inventory_item_id')
                ->with('inventoryItem:id,minimum_stock_level,default_purchase_price')
                ->get()
            : collect();

        $outOfStockCount = $stockLevels
            ->filter(static fn (StockMovement $stockLevel): bool => (float) $stockLevel->total_qty <= 0)
            ->count();

        $lowStockCount = $stockLevels
            ->filter(static function (StockMovement $stockLevel): bool {
                $minimumStockLevel = (float) ($stockLevel->inventoryItem?->minimum_stock_level ?? 0);
                $totalQuantity = (float) $stockLevel->total_qty;

                return $totalQuantity <= $minimumStockLevel && $totalQuantity > 0;
            })
            ->count();

        $totalStockValue = $stockLevels
            ->sum(static fn (StockMovement $stockLevel): float => (float) $stockLevel->total_qty
                * (float) ($stockLevel->inventoryItem?->default_purchase_price ?? 0));

        $expiringSoonCount = is_string($activeBranchId) && $activeBranchId !== '' && $locationIds !== []
            ? InventoryBatch::query()
                ->where('branch_id', $activeBranchId)
                ->whereIn('inventory_location_id', $locationIds)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '>', now())
                ->where('expiry_date', '<=', now()->addDays(30))
                ->count()
            : 0;

        $distributionByType = InventoryItem::query()
            ->select('item_type')
            ->selectRaw('count(*) as count')
            ->groupBy('item_type')
            ->get()
            ->mapWithKeys(static fn (InventoryItem $item): array => [$item->item_type->value => $item->count])
            ->toArray();

        $distributionByCategory = InventoryItem::query()
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('count(*) as count')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(static fn (InventoryItem $item): array => [$item->category->value => $item->count])
            ->toArray();

        $recentItems = InventoryItem::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'generic_name', 'item_type', 'created_at']);

        $poStats = PurchaseOrder::query()
            ->select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(static fn (PurchaseOrder $po): array => [$po->status->value => $po->count])
            ->toArray();

        return Inertia::render('inventory/dashboard', [
            'stats' => [
                'out_of_stock' => $outOfStockCount,
                'low_stock' => $lowStockCount,
                'expiring_soon' => $expiringSoonCount,
                'total_value' => $totalStockValue,
                'total_items' => InventoryItem::query()->count(),
                'active_items' => InventoryItem::query()->where('is_active', true)->count(),
                'drug_items' => InventoryItem::query()->drugs()->count(),
                'expirable_items' => InventoryItem::query()->where('expires', true)->count(),
                'total_locations' => $accessibleLocations->count(),
                'dispensing_locations' => $accessibleLocations
                    ->filter(static fn (InventoryLocation $location): bool => $location->is_dispensing_point)
                    ->count(),
                'total_suppliers' => Supplier::query()->active()->count(),
                'distribution_by_type' => $distributionByType,
                'distribution_by_category' => $distributionByCategory,
                'recent_items' => $recentItems,
                'po_stats' => $poStats,
            ],
        ]);
    }
}
