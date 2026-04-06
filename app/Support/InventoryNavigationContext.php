<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

final class InventoryNavigationContext
{
    /**
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        $key = self::resolveKey($request);

        return match ($key) {
            'laboratory' => [
                'key' => 'laboratory',
                'section_title' => 'Laboratory',
                'section_href' => '/laboratory/dashboard',
                'stock_title' => 'Lab Stock',
                'stock_href' => '/laboratory/stock',
                'requisitions_title' => 'Lab Requisitions',
                'requisitions_href' => '/laboratory/requisitions',
                'requisition_create_title' => 'Create Lab Requisition',
                'movements_title' => 'Lab Movements',
                'movements_href' => '/laboratory/movements',
                'receipts_title' => 'Lab Receipts',
                'receipts_href' => '/laboratory/receipts',
                'receipt_create_title' => 'Create Lab Receipt',
            ],
            'pharmacy' => [
                'key' => 'pharmacy',
                'section_title' => 'Pharmacy',
                'section_href' => '/pharmacy/stock',
                'stock_title' => 'Pharmacy Stock',
                'stock_href' => '/pharmacy/stock',
                'requisitions_title' => 'Pharmacy Requisitions',
                'requisitions_href' => '/pharmacy/requisitions',
                'requisition_create_title' => 'Create Pharmacy Requisition',
                'movements_title' => 'Pharmacy Movements',
                'movements_href' => '/pharmacy/movements',
                'receipts_title' => 'Pharmacy Receipts',
                'receipts_href' => '/pharmacy/receipts',
                'receipt_create_title' => 'Create Pharmacy Receipt',
            ],
            default => [
                'key' => 'inventory',
                'section_title' => 'Inventory',
                'section_href' => '/inventory/dashboard',
                'stock_title' => 'Stock By Location',
                'stock_href' => '/inventory/stock-by-location',
                'requisitions_title' => 'Requisitions',
                'requisitions_href' => '/inventory-requisitions',
                'requisition_create_title' => 'Create Requisition',
                'movements_title' => 'Stock Movements',
                'movements_href' => '/inventory/reports/movements',
                'receipts_title' => 'Goods Receipts',
                'receipts_href' => '/goods-receipts',
                'receipt_create_title' => 'Create Goods Receipt',
            ],
        };
    }

    public static function query(Request $request): ?string
    {
        $key = self::resolveKey($request);

        return $key === 'inventory' ? null : $key;
    }

    private static function resolveKey(Request $request): string
    {
        $queryContext = mb_trim((string) $request->query('context', ''));

        if (in_array($queryContext, ['laboratory', 'pharmacy'], true)) {
            return $queryContext;
        }

        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'laboratory.')) {
            return 'laboratory';
        }

        if (is_string($routeName) && str_starts_with($routeName, 'pharmacy.')) {
            return 'pharmacy';
        }

        return 'inventory';
    }
}
