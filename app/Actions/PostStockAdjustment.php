<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PostStockAdjustment
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public function handle(StockAdjustment $stockAdjustment): StockAdjustment
    {
        return DB::transaction(function () use ($stockAdjustment): StockAdjustment {
            $updatedRows = StockAdjustment::query()
                ->whereKey($stockAdjustment->id)
                ->where('status', StockAdjustmentStatus::Draft)
                ->update([
                    'status' => StockAdjustmentStatus::Posted,
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

            abort_unless(
                $updatedRows === 1,
                422,
                'Only draft stock adjustments can be posted.',
            );

            $stockAdjustment = StockAdjustment::query()
                ->with('items.inventoryBatch')
                ->findOrFail($stockAdjustment->id);

            $this->guardAvailableQuantities($stockAdjustment);

            foreach ($stockAdjustment->items as $item) {
                $batch = $this->resolveBatch($stockAdjustment, $item);

                StockMovement::query()->create([
                    'tenant_id' => $stockAdjustment->tenant_id,
                    'branch_id' => $stockAdjustment->branch_id,
                    'inventory_location_id' => $stockAdjustment->inventory_location_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'inventory_batch_id' => $batch?->id,
                    'movement_type' => (float) $item->quantity_delta > 0
                        ? StockMovementType::AdjustmentGain
                        : StockMovementType::AdjustmentLoss,
                    'quantity' => $item->quantity_delta,
                    'unit_cost' => $item->unit_cost,
                    'source_document_type' => StockAdjustment::class,
                    'source_document_id' => $stockAdjustment->id,
                    'source_line_type' => StockAdjustmentItem::class,
                    'source_line_id' => $item->id,
                    'notes' => $item->notes ?? $stockAdjustment->reason,
                    'occurred_at' => $stockAdjustment->posted_at ?? now(),
                    'created_by' => Auth::id(),
                ]);
            }

            return $stockAdjustment->refresh()->load('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation');
        });
    }

    private function guardAvailableQuantities(StockAdjustment $stockAdjustment): void
    {
        $locationBalances = $this->inventoryStockLedger
            ->summarizeByLocation($stockAdjustment->branch_id)
            ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $stockAdjustment->inventory_location_id)
            ->mapWithKeys(static fn (array $balance): array => [
                $balance['inventory_item_id'] => $balance['quantity'],
            ]);

        $batchBalances = $this->inventoryStockLedger
            ->summarizeByBatch($stockAdjustment->branch_id)
            ->mapWithKeys(static fn (array $balance): array => [
                $balance['inventory_batch_id'] => $balance['quantity'],
            ]);

        foreach ($stockAdjustment->items as $item) {
            if ((float) $item->quantity_delta >= 0) {
                continue;
            }

            $lossQuantity = abs((float) $item->quantity_delta);

            if (is_string($item->inventory_batch_id) && $item->inventory_batch_id !== '') {
                abort_unless(
                    $lossQuantity <= (float) ($batchBalances[$item->inventory_batch_id] ?? 0.0),
                    422,
                    'A stock loss cannot exceed the selected batch balance.',
                );

                continue;
            }

            abort_unless(
                $lossQuantity <= (float) ($locationBalances[$item->inventory_item_id] ?? 0.0),
                422,
                'A stock loss cannot exceed the location balance.',
            );
        }
    }

    private function resolveBatch(StockAdjustment $stockAdjustment, StockAdjustmentItem $item): ?InventoryBatch
    {
        if (is_string($item->inventory_batch_id) && $item->inventory_batch_id !== '') {
            return InventoryBatch::query()->find($item->inventory_batch_id);
        }

        if ((float) $item->quantity_delta <= 0) {
            return null;
        }

        return InventoryBatch::query()->create([
            'tenant_id' => $stockAdjustment->tenant_id,
            'branch_id' => $stockAdjustment->branch_id,
            'inventory_location_id' => $stockAdjustment->inventory_location_id,
            'inventory_item_id' => $item->inventory_item_id,
            'goods_receipt_item_id' => null,
            'batch_number' => $item->batch_number,
            'expiry_date' => $item->expiry_date,
            'unit_cost' => $item->unit_cost ?? 0,
            'quantity_received' => abs((float) $item->quantity_delta),
            'received_at' => $stockAdjustment->posted_at ?? now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
