<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\FacilityBranch;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationItem;
use App\Models\User;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Collection;

final readonly class GenerateStockLevelReportAction
{
    public function __construct(
        private InventoryStockLedger $ledger,
        private InventoryLocationAccess $locationAccess,
    ) {}

    /**
     * @return array{
     *     branch_name: string|null,
     *     total_items: int,
     *     low_stock_count: int,
     *     out_of_stock_count: int,
     *     selected_location_id: string|null,
     *     locations: Collection<int, array{id: string, name: string, code: string|null}>,
     *     rows: Collection<int, array{
     *         item_id: string,
     *         item_name: string,
     *         dosage_info: string,
     *         unit: string|null,
     *         location_id: string,
     *         location_name: string,
     *         location_code: string,
     *         minimum_stock_level: float,
     *         reorder_level: float,
     *         quantity: float,
     *         status: string
     *     }>
     * }
     */
    public function handle(string $branchId, ?User $user = null, ?string $locationId = null): array
    {
        /** @var Collection<int, InventoryLocation> $locations */
        $locations = $this->locationAccess->accessibleLocations($user, $branchId);

        if (is_string($locationId) && $locationId !== '') {
            $locations = $locations
                ->filter(fn (InventoryLocation $location): bool => $location->id === $locationId)
                ->values();
        }

        if ($locations->isEmpty()) {
            /** @var Collection<int, array{id: string, name: string, code: string|null}> $emptyLocations */
            $emptyLocations = collect([]);
            /** @var Collection<int, array{
             *     item_id: string,
             *     item_name: string,
             *     dosage_info: string,
             *     unit: string|null,
             *     location_id: string,
             *     location_name: string,
             *     location_code: string,
             *     minimum_stock_level: float,
             *     reorder_level: float,
             *     quantity: float,
             *     status: string
             * }> $emptyRows
             */
            $emptyRows = collect([]);

            return [
                'branch_name' => null,
                'total_items' => 0,
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'selected_location_id' => null,
                'locations' => $emptyLocations,
                'rows' => $emptyRows,
            ];
        }

        $locationIds = $locations->pluck('id')->filter()->values()->all();

        $ledgerBalances = $this->ledger
            ->summarizeByLocation($branchId)
            ->filter(fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
            ->keyBy(fn (array $balance): string => $balance['inventory_item_id'].':'.$balance['inventory_location_id']);

        $locationItems = InventoryLocationItem::query()
            ->where('branch_id', $branchId)
            ->whereIn('inventory_location_id', $locationIds)
            ->with([
                'item:id,name,generic_name,dosage_form,strength,minimum_stock_level,reorder_level,unit_id',
                'item.unit:id,symbol',
                'location:id,name,location_code',
            ])
            ->get();

        /** @var array<string, array{
         *     item_id: string,
         *     item_name: string,
         *     dosage_info: string,
         *     unit: string|null,
         *     location_id: string,
         *     location_name: string,
         *     location_code: string,
         *     minimum_stock_level: float,
         *     reorder_level: float,
         *     quantity: float,
         *     status: string
         * }> $rows
         */
        $rows = [];

        foreach ($locationItems as $locationItem) {
            $item = $locationItem->item;
            $location = $locationItem->location;
            if ($item === null) {
                continue;
            }

            if ($location === null) {
                continue;
            }

            $key = $item->id.':'.$locationItem->inventory_location_id;
            $quantity = (float) ($ledgerBalances[$key]['quantity'] ?? 0.0);
            $minLevel = (float) ($locationItem->minimum_stock_level ?? $item->minimum_stock_level ?? 0);
            $reorder = (float) ($locationItem->reorder_level ?? $item->reorder_level ?? 0);

            $status = match (true) {
                $quantity <= 0 => 'out_of_stock',
                $quantity <= $minLevel => 'critical',
                $quantity <= $reorder => 'low',
                default => 'ok',
            };

            $rows[$key] = [
                'item_id' => $item->id,
                'item_name' => $item->generic_name ?? $item->name,
                'dosage_info' => mb_trim(($item->dosage_form->value ?? '').' '.($item->strength ?? '')),
                'unit' => $item->unit?->symbol,
                'location_id' => $location->id,
                'location_name' => $location->name,
                'location_code' => $location->location_code ?? '-',
                'minimum_stock_level' => $minLevel,
                'reorder_level' => $reorder,
                'quantity' => $quantity,
                'status' => $status,
            ];
        }

        /** @var Collection<int, array{
         *     item_id: string,
         *     item_name: string,
         *     dosage_info: string,
         *     unit: string|null,
         *     location_id: string,
         *     location_name: string,
         *     location_code: string,
         *     minimum_stock_level: float,
         *     reorder_level: float,
         *     quantity: float,
         *     status: string
         * }> $collection
         */
        $collection = collect(array_values($rows))->sortBy('item_name')->values();
        $branchName = FacilityBranch::query()->whereKey($branchId)->value('name');

        /** @var Collection<int, array{id: string, name: string, code: string|null}> $locationOptions */
        $locationOptions = $locations
            ->map(static fn (InventoryLocation $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->location_code,
            ])
            ->values();

        return [
            'branch_name' => is_string($branchName) ? $branchName : null,
            'total_items' => $collection->count(),
            'low_stock_count' => $collection->whereIn('status', ['low', 'critical'])->count(),
            'out_of_stock_count' => $collection->where('status', 'out_of_stock')->count(),
            'selected_location_id' => is_string($locationId) && $locationId !== '' ? $locationId : null,
            'locations' => $locationOptions,
            'rows' => $collection,
        ];
    }
}
