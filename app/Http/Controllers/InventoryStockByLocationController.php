<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationItem;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use App\Support\InventoryWorkspace;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryStockByLocationController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 15;

        ['rows' => $allRows, 'locations' => $locations] = $this->buildRows(
            $workspace->locationTypeValues(),
            $workspace->key(),
        );

        $rows = $allRows
            ->when(
                $search !== '',
                static fn (Collection $collection): Collection => $collection->filter(
                    static fn (array $row): bool => str_contains(mb_strtolower(sprintf(
                        '%s %s',
                        $row['item_name'],
                        $row['item_type'],
                    )), mb_strtolower($search)),
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

        return Inertia::render($workspace->stockComponent(), [
            'rows' => $paginator,
            'filters' => [
                'search' => $search,
                'type' => $type !== '' ? $type : null,
            ],
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'itemTypes' => collect(InventoryItemType::cases())
                ->map(static fn (InventoryItemType $itemType): array => [
                    'value' => $itemType->value,
                    'label' => $itemType->label(),
                ])
                ->values(),
            'locations' => $locations,
            'note' => 'Balances now reflect posted stock movements, grouped by item and location. Quantities combine all batches currently received into each location.',
        ]);
    }

    /**
     * @param  list<InventoryLocationType|string>  $locationTypes
     * @return array{
     *     rows: Collection<int, array{
     *         item_id: string,
     *         item_name: string,
     *         item_type: string,
     *         unit: string|null,
     *         minimum_stock_level: float,
     *         total_quantity: float,
     *         location_quantities: array<string, float>
     *     }>,
     *     locations: Collection<int, array{
     *         id: string,
     *         name: string,
     *         code: string,
     *         type: string,
     *         label: string
     *     }>
     * }
     */
    private function buildRows(array $locationTypes = [], ?string $workspaceKey = null): array
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        if (! is_string($activeBranchId) || $activeBranchId === '') {
            /** @var Collection<int, array{
             *   item_id: string,
             *   item_name: string,
             *   item_type: string,
             *   unit: string|null,
             *   minimum_stock_level: float,
             *   total_quantity: float,
             *   location_quantities: array<string, float>
             * }> $emptyRows
             */
            $emptyRows = collect();
            /** @var Collection<int, array{id: string, name: string, code: string, type: string, label: string}> $emptyLocations */
            $emptyLocations = collect();

            return [
                'rows' => $emptyRows,
                'locations' => $emptyLocations,
            ];
        }

        $accessibleLocations = $this->inventoryLocationAccess->accessibleLocations(Auth::user(), $activeBranchId, $locationTypes);

        if ($accessibleLocations->isEmpty()) {
            /** @var Collection<int, array{
             *   item_id: string,
             *   item_name: string,
             *   item_type: string,
             *   unit: string|null,
             *   minimum_stock_level: float,
             *   total_quantity: float,
             *   location_quantities: array<string, float>
             * }> $emptyRows
             */
            $emptyRows = collect();
            /** @var Collection<int, array{id: string, name: string, code: string, type: string, label: string}> $emptyLocations */
            $emptyLocations = collect();

            return [
                'rows' => $emptyRows,
                'locations' => $emptyLocations,
            ];
        }

        $locationIds = $accessibleLocations
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        /** @var Collection<int, array{id: string, name: string, code: string, type: string, label: string}> $locations */
        $locations = $accessibleLocations
            ->map(static fn (InventoryLocation $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->location_code,
                'type' => $location->type->value,
                'label' => sprintf('%s (%s)', $location->name, $location->location_code),
            ])
            ->values();

        /** @var array<string, float> $locationQuantitiesTemplate */
        $locationQuantitiesTemplate = $locations
            ->mapWithKeys(static fn (array $location): array => [$location['id'] => 0.0])
            ->all();

        $locationItems = InventoryLocationItem::query()
            ->where('branch_id', $activeBranchId)
            ->whereIn('inventory_location_id', $locationIds)
            ->with([
                'item:id,name,generic_name,item_type,minimum_stock_level,reorder_level,unit_id',
                'item.unit:id,name,symbol',
            ])
            ->get();

        /** @var array<string, InventoryLocationItem> $locationItemLookup */
        $locationItemLookup = $locationItems
            ->mapWithKeys(static fn (InventoryLocationItem $locationItem): array => [
                sprintf('%s:%s', $locationItem->inventory_location_id, $locationItem->inventory_item_id) => $locationItem,
            ])
            ->all();

        /** @var array<string, InventoryItem> $itemLookup */
        $itemLookup = $locationItems
            ->mapWithKeys(static fn (InventoryLocationItem $locationItem): array => $locationItem->item !== null
                ? [$locationItem->inventory_item_id => $locationItem->item]
                : [])
            ->all();

        /** @var array<string, array{
         *   item_id: string,
         *   item_name: string,
         *   item_type: string,
         *   unit: string|null,
         *   minimum_stock_level: float,
         *   total_quantity: float,
         *   location_quantities: array<string, float>
         * }> $rows */
        $rows = [];

        $firstLocation = $accessibleLocations->first();
        $tenantId = $firstLocation->tenant_id;

        $locationItems
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
                    'item_type' => $item->item_type->value,
                    'unit' => $item->unit?->symbol,
                    'minimum_stock_level' => (float) $locationItem->minimum_stock_level,
                    'total_quantity' => 0.0,
                    'location_quantities' => $locationQuantitiesTemplate,
                ];
            });

        if (in_array($workspaceKey, ['laboratory', 'pharmacy'], true) && $tenantId !== '') {
            InventoryItem::query()
                ->where('tenant_id', $tenantId)
                ->active()
                ->with('unit:id,name,symbol')
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'item_type', 'minimum_stock_level', 'unit_id'])
                ->each(function (InventoryItem $item) use (&$rows, $locationQuantitiesTemplate): void {
                    if (array_key_exists($item->id, $rows)) {
                        return;
                    }

                    $rows[$item->id] = [
                        'item_id' => $item->id,
                        'item_name' => $item->generic_name ?? $item->name,
                        'item_type' => $item->item_type->value,
                        'unit' => $item->unit?->symbol,
                        'minimum_stock_level' => (float) $item->minimum_stock_level,
                        'total_quantity' => 0.0,
                        'location_quantities' => $locationQuantitiesTemplate,
                    ];
                });
        }

        $this->inventoryStockLedger
            ->summarizeByLocation($activeBranchId)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
            ->each(function (array $balance) use (&$rows, $itemLookup, $locationItemLookup, $locationQuantitiesTemplate): void {
                $locationItem = $locationItemLookup[sprintf(
                    '%s:%s',
                    $balance['inventory_location_id'],
                    $balance['inventory_item_id'],
                )] ?? null;

                $item = $itemLookup[$balance['inventory_item_id']] ?? null;

                if ($item === null) {
                    $item = InventoryItem::query()
                        ->with('unit:id,name,symbol')
                        ->find($balance['inventory_item_id']);
                }

                if ($item === null) {
                    return;
                }

                $key = $item->id;

                if (! array_key_exists($key, $rows)) {
                    $rows[$key] = [
                        'item_id' => $item->id,
                        'item_name' => $item->generic_name ?? $item->name,
                        'item_type' => $item->item_type->value,
                        'unit' => $item->unit?->symbol,
                        'minimum_stock_level' => $locationItem instanceof InventoryLocationItem
                            ? (float) $locationItem->minimum_stock_level
                            : (float) $item->minimum_stock_level,
                        'total_quantity' => 0.0,
                        'location_quantities' => $locationQuantitiesTemplate,
                    ];
                }

                $rows[$key]['location_quantities'][$balance['inventory_location_id']] = $balance['quantity'];
                $rows[$key]['total_quantity'] += $balance['quantity'];
            });

        /** @var Collection<int, array{
         *   item_id: string,
         *   item_name: string,
         *   item_type: string,
         *   unit: string|null,
         *   minimum_stock_level: float,
         *   total_quantity: float,
         *   location_quantities: array<string, float>
         * }> $rowCollection */
        $rowCollection = collect($rows)
            ->map(function (array $row) use ($locations): array {
                $row['location_quantities'] = $locations
                    ->mapWithKeys(static fn (array $location): array => [
                        $location['id'] => $row['location_quantities'][$location['id']] ?? 0.0,
                    ])
                    ->all();

                return $row;
            })
            ->sortBy([
                ['item_name', 'asc'],
            ])
            ->values();

        return [
            'rows' => $rowCollection,
            'locations' => $locations,
        ];
    }
}
