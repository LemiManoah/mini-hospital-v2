<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockCountStatus;
use App\Enums\StockMovementType;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PostStockCount
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public function handle(StockCount $stockCount): StockCount
    {
        return DB::transaction(function () use ($stockCount): StockCount {
            $stockCount = StockCount::query()
                ->with('items.inventoryItem')
                ->findOrFail($stockCount->id);

            $this->guardCurrentBalances($stockCount);

            $updatedRows = StockCount::query()
                ->whereKey($stockCount->id)
                ->where('status', StockCountStatus::Draft)
                ->update([
                    'status' => StockCountStatus::Posted,
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

            abort_unless(
                $updatedRows === 1,
                422,
                'Only draft stock counts can be posted.',
            );

            $stockCount = StockCount::query()
                ->with('items.inventoryItem', 'inventoryLocation')
                ->findOrFail($stockCount->id);

            foreach ($stockCount->items as $item) {
                $varianceQuantity = (float) $item->variance_quantity;

                if (abs($varianceQuantity) < 0.0005) {
                    continue;
                }

                StockMovement::query()->create([
                    'tenant_id' => $stockCount->tenant_id,
                    'branch_id' => $stockCount->branch_id,
                    'inventory_location_id' => $stockCount->inventory_location_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'inventory_batch_id' => null,
                    'movement_type' => $varianceQuantity > 0
                        ? StockMovementType::AdjustmentGain
                        : StockMovementType::AdjustmentLoss,
                    'quantity' => $varianceQuantity,
                    'unit_cost' => $item->inventoryItem?->default_purchase_price ?? 0,
                    'source_document_type' => StockCount::class,
                    'source_document_id' => $stockCount->id,
                    'source_line_type' => StockCountItem::class,
                    'source_line_id' => $item->id,
                    'notes' => $item->notes ?? 'Stock count variance',
                    'occurred_at' => $stockCount->posted_at ?? now(),
                    'created_by' => Auth::id(),
                ]);
            }

            return $stockCount;
        });
    }

    private function guardCurrentBalances(StockCount $stockCount): void
    {
        $currentBalances = $this->inventoryStockLedger
            ->summarizeByLocation($stockCount->branch_id)
            ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $stockCount->inventory_location_id)
            ->mapWithKeys(static fn (array $balance): array => [
                $balance['inventory_item_id'] => $balance['quantity'],
            ]);

        foreach ($stockCount->items as $item) {
            $currentQuantity = (float) ($currentBalances[$item->inventory_item_id] ?? 0.0);
            $expectedQuantity = (float) $item->expected_quantity;

            abort_unless(
                abs($currentQuantity - $expectedQuantity) < 0.0005,
                422,
                'Stock moved after this count was recorded. Start a new count from the current balance.',
            );
        }
    }
}
