<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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
        $activeBranchId = \App\Support\BranchContext::getActiveBranchId();

        // Calculate Stock on Hand from posted goods receipts (simplified logic based on buildRows in InventoryStockByLocationController)
        $stockLevels = \App\Models\GoodsReceiptItem::query()
            ->whereHas('goodsReceipt', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId)
                    ->where('status', \App\Enums\GoodsReceiptStatus::Posted);
            })
            ->select('inventory_item_id')
            ->selectRaw('SUM(quantity_received) as total_qty')
            ->groupBy('inventory_item_id')
            ->with('inventoryItem:id,minimum_stock_level,default_purchase_price')
            ->get();

        $outOfStockCount = InventoryItem::query()->whereDoesntHave('locationItems')->count(); // Initial assumption
        // Refine out of stock: items that should be in stock but have 0 qty
        $outOfStockCount = $stockLevels->filter(fn($s) => $s->total_qty <= 0)->count();

        // Items below minimum stock
        $lowStockCount = $stockLevels->filter(function($s) {
            return $s->total_qty <= ($s->inventoryItem->minimum_stock_level ?? 0) && $s->total_qty > 0;
        })->count();

        // Total monetary value
        $totalStockValue = $stockLevels->sum(function($s) {
            return $s->total_qty * ($s->inventoryItem->default_purchase_price ?? 0);
        });

        // Expiring soon (next 30 days)
        $expiringSoonCount = \App\Models\GoodsReceiptItem::query()
            ->whereHas('goodsReceipt', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId)
                    ->where('status', \App\Enums\GoodsReceiptStatus::Posted);
            })
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->count();

        $distributionByType = InventoryItem::query()
            ->select('item_type')
            ->selectRaw('count(*) as count')
            ->groupBy('item_type')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->item_type->value => $item->count])
            ->toArray();

        $distributionByCategory = InventoryItem::query()
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('count(*) as count')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->category->value => $item->count])
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
            ->mapWithKeys(fn ($po) => [$po->status->value => $po->count])
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
