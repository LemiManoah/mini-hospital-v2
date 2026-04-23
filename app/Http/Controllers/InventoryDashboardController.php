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
            ->filter(static fn (StockMovement $stockLevel): bool => self::floatValue($stockLevel->getAttribute('total_qty')) <= 0)
            ->count();

        $lowStockCount = $stockLevels
            ->filter(static function (StockMovement $stockLevel): bool {
                $inventoryItem = $stockLevel->inventoryItem;
                $minimumStockLevel = $inventoryItem instanceof InventoryItem
                    ? (float) $inventoryItem->minimum_stock_level
                    : 0.0;
                $totalQuantity = self::floatValue($stockLevel->getAttribute('total_qty'));

                return $totalQuantity <= $minimumStockLevel && $totalQuantity > 0;
            })
            ->count();

        $totalStockValue = $stockLevels
            ->sum(static function (StockMovement $stockLevel): float {
                $inventoryItem = $stockLevel->inventoryItem;
                $defaultPurchasePrice = $inventoryItem instanceof InventoryItem
                    ? (float) $inventoryItem->default_purchase_price
                    : 0.0;

                return self::floatValue($stockLevel->getAttribute('total_qty')) * $defaultPurchasePrice;
            });

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
            ->selectRaw('count(*) as aggregate_count')
            ->groupBy('item_type')
            ->get()
            ->reduce(
                static function (array $carry, InventoryItem $item): array {
                    $carry[$item->item_type->value] = self::intValue($item->getAttribute('aggregate_count'));

                    return $carry;
                },
                [],
            );

        $distributionByCategory = InventoryItem::query()
            ->whereNotNull('category')
            ->select('category')
            ->selectRaw('count(*) as aggregate_count')
            ->groupBy('category')
            ->get()
            ->reduce(
                static function (array $carry, InventoryItem $item): array {
                    if ($item->category === null) {
                        return $carry;
                    }

                    $carry[$item->category->value] = self::intValue($item->getAttribute('aggregate_count'));

                    return $carry;
                },
                [],
            );

        $recentItems = InventoryItem::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'generic_name', 'item_type', 'created_at']);

        $poStats = PurchaseOrder::query()
            ->select('status')
            ->selectRaw('count(*) as aggregate_count')
            ->groupBy('status')
            ->get()
            ->reduce(
                static function (array $carry, PurchaseOrder $purchaseOrder): array {
                    $carry[$purchaseOrder->status->value] = self::intValue($purchaseOrder->getAttribute('aggregate_count'));

                    return $carry;
                },
                [],
            );

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

    private static function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private static function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
