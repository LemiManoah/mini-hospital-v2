<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class InventoryStockLedger
{
    /**
     * @return Collection<int, array{
     *     inventory_location_id: string,
     *     inventory_item_id: string,
     *     quantity: float
     * }>
     */
    public function summarizeByLocation(string $branchId): Collection
    {
        /** @var Collection<int, object{
         *     inventory_location_id: string,
         *     inventory_item_id: string,
         *     quantity: int|float|string
         * }> $rows
         */
        $rows = collect(
            DB::table('stock_movements')
                ->select('inventory_location_id', 'inventory_item_id', DB::raw('SUM(quantity) as quantity'))
                ->where('branch_id', $branchId)
                ->groupBy('inventory_location_id', 'inventory_item_id')
                ->get()
        );

        return $rows->map(static fn (object $row): array => [
            'inventory_location_id' => (string) $row->inventory_location_id,
            'inventory_item_id' => (string) $row->inventory_item_id,
            'quantity' => (float) $row->quantity,
        ]);
    }

    /**
     * @return Collection<int, array{
     *     inventory_batch_id: string,
     *     inventory_location_id: string,
     *     inventory_item_id: string,
     *     batch_number: string|null,
     *     expiry_date: string|null,
     *     quantity: float
     * }>
     */
    public function summarizeByBatch(string $branchId): Collection
    {
        /** @var Collection<int, object{
         *     inventory_batch_id: string,
         *     inventory_location_id: string,
         *     inventory_item_id: string,
         *     batch_number: string|null,
         *     expiry_date: string|null,
         *     quantity: int|float|string
         * }> $rows
         */
        $rows = collect(
            DB::table('stock_movements')
                ->join('inventory_batches', 'inventory_batches.id', '=', 'stock_movements.inventory_batch_id')
                ->select(
                    'stock_movements.inventory_batch_id',
                    'stock_movements.inventory_location_id',
                    'stock_movements.inventory_item_id',
                    'inventory_batches.batch_number',
                    'inventory_batches.expiry_date',
                    DB::raw('SUM(stock_movements.quantity) as quantity'),
                )
                ->where('stock_movements.branch_id', $branchId)
                ->whereNotNull('stock_movements.inventory_batch_id')
                ->groupBy(
                    'stock_movements.inventory_batch_id',
                    'stock_movements.inventory_location_id',
                    'stock_movements.inventory_item_id',
                    'inventory_batches.batch_number',
                    'inventory_batches.expiry_date',
                )
                ->get()
        );

        return $rows->map(static fn (object $row): array => [
            'inventory_batch_id' => (string) $row->inventory_batch_id,
            'inventory_location_id' => (string) $row->inventory_location_id,
            'inventory_item_id' => (string) $row->inventory_item_id,
            'batch_number' => $row->batch_number !== null ? (string) $row->batch_number : null,
            'expiry_date' => $row->expiry_date !== null ? (string) $row->expiry_date : null,
            'quantity' => (float) $row->quantity,
        ]);
    }
}
