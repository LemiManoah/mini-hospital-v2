<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryItemType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationItem;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryStockByLocationController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 15;

        ['rows' => $allRows, 'locations' => $locations] = $this->buildRows();

        $rows = $allRows
            ->when(
                $search !== '',
                static fn (Collection $collection): Collection => $collection->filter(
                    static fn (array $row): bool => str_contains(
                        mb_strtolower(implode(' ', [
                            $row['item_name'],
                            $row['item_type'],
                        ])),
                        mb_strtolower($search),
                    ),
                )
            )
            ->when(
                $type !== '',
                static fn (Collection $collection): Collection => $collection->where('item_type', $type),
            )
            ->values();

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return Inertia::render('inventory/stock-by-location/index', [
            'rows' => $paginator,
            'filters' => [
                'search' => $search,
                'type' => $type !== '' ? $type : null,
            ],
            'itemTypes' => collect(InventoryItemType::cases())
                ->map(static fn (InventoryItemType $itemType): array => [
                    'value' => $itemType->value,
                    'label' => $itemType->label(),
                ])
                ->values(),
            'locations' => $locations,
            'note' => 'Balances currently reflect posted goods receipts and configured location-item records. They will move to the stock ledger once milestone 3 inventory movements are implemented.',
        ]);
    }

    /**
     * @return array{
     *     rows: Collection<int, array<string, mixed>>,
     *     locations: Collection<int, array<string, mixed>>
     * }
     */
    private function buildRows(): array
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        if (! is_string($activeBranchId) || $activeBranchId === '') {
            return [
                'rows' => collect(),
                'locations' => collect(),
            ];
        }

        $locations = InventoryLocation::query()
            ->where('branch_id', $activeBranchId)
            ->orderBy('name')
            ->get(['id', 'name', 'location_code', 'type'])
            ->map(static fn (InventoryLocation $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->location_code,
                'type' => $location->type?->value,
                'label' => sprintf('%s (%s)', $location->name, $location->location_code),
            ])
            ->values();

        /** @var array<string, float> $locationQuantitiesTemplate */
        $locationQuantitiesTemplate = $locations
            ->mapWithKeys(static fn (array $location): array => [$location['id'] => 0.0])
            ->all();

        /** @var array<string, array<string, mixed>> $rows */
        $rows = [];

        InventoryLocationItem::query()
            ->where('branch_id', $activeBranchId)
            ->with([
                'item:id,name,generic_name,item_type,minimum_stock_level,reorder_level,unit_id',
                'item.unit:id,name,symbol',
            ])
            ->get()
            ->each(function (InventoryLocationItem $locationItem) use (&$rows, $locationQuantitiesTemplate): void {
                $item = $locationItem->item;

                if ($item === null) {
                    return;
                }

                if (array_key_exists($locationItem->inventory_item_id, $rows)) {
                    return;
                }

                $rows[$locationItem->inventory_item_id] = [
                    'item_id' => $item->id,
                    'item_name' => $item->generic_name ?? $item->name,
                    'item_type' => $item->item_type?->value,
                    'unit' => $item->unit?->symbol,
                    'minimum_stock_level' => (float) $locationItem->minimum_stock_level,
                    'total_quantity' => 0.0,
                    'location_quantities' => $locationQuantitiesTemplate,
                ];
            });

        GoodsReceipt::query()
            ->where('branch_id', $activeBranchId)
            ->with([
                'inventoryLocation:id,name,location_code,type',
                'items:id,goods_receipt_id,inventory_item_id,quantity_received',
                'items.inventoryItem:id,name,generic_name,item_type,minimum_stock_level,reorder_level,unit_id',
                'items.inventoryItem.unit:id,name,symbol',
            ])
            ->where('status', GoodsReceiptStatus::Posted)
            ->orderByDesc('receipt_date')
            ->get()
            ->each(function (GoodsReceipt $goodsReceipt) use (&$rows, $locationQuantitiesTemplate): void {
                $location = $goodsReceipt->inventoryLocation;

                if ($location === null) {
                    return;
                }

                $goodsReceipt->items->each(function (GoodsReceiptItem $receiptItem) use (&$rows, $location, $locationQuantitiesTemplate): void {
                    $item = $receiptItem->inventoryItem;

                    if ($item === null) {
                        return;
                    }

                    $key = $item->id;

                    if (! array_key_exists($key, $rows)) {
                        $rows[$key] = [
                            'item_id' => $item->id,
                            'item_name' => $item->generic_name ?? $item->name,
                            'item_type' => $item->item_type?->value,
                            'unit' => $item->unit?->symbol,
                            'minimum_stock_level' => (float) $item->minimum_stock_level,
                            'total_quantity' => 0.0,
                            'location_quantities' => $locationQuantitiesTemplate,
                        ];
                    }

                    if (! array_key_exists($location->id, $rows[$key]['location_quantities'])) {
                        $rows[$key]['location_quantities'][$location->id] = 0.0;
                    }

                    $rows[$key]['location_quantities'][$location->id] += (float) $receiptItem->quantity_received;
                    $rows[$key]['total_quantity'] += (float) $receiptItem->quantity_received;
                });
            });

        return [
            'rows' => collect($rows)
                ->map(function (array $row) use ($locations): array {
                    $row['location_quantities'] = $locations
                        ->mapWithKeys(static fn (array $location): array => [
                            $location['id'] => (float) ($row['location_quantities'][$location['id']] ?? 0.0),
                        ])
                        ->all();

                    return $row;
                })
                ->sortBy([
                    ['item_name', 'asc'],
                ])
                ->values(),
            'locations' => $locations,
        ];
    }
}
