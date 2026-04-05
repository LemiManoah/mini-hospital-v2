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
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryDashboardController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(): Response
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        $stockLevels = StockMovement::query()
            ->where('branch_id', $activeBranchId)
            ->select('inventory_item_id')
            ->selectRaw('SUM(quantity) as total_qty')
            ->groupBy('inventory_item_id')
            ->with('inventoryItem:id,minimum_stock_level,default_purchase_price')
            ->get();

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

        $expiringSoonCount = InventoryBatch::query()
            ->where('branch_id', $activeBranchId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->count();

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
                'total_locations' => InventoryLocation::query()->count(),
                'dispensing_locations' => InventoryLocation::query()->where('is_dispensing_point', true)->count(),
                'total_suppliers' => Supplier::query()->active()->count(),
                'distribution_by_type' => $distributionByType,
                'distribution_by_category' => $distributionByCategory,
                'recent_items' => $recentItems,
                'po_stats' => $poStats,
            ],
        ]);
    }
}
