<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use App\Models\StockMovement;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PostInventoryReconciliation
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public function handle(Reconciliation $reconciliation): Reconciliation
    {
        return DB::transaction(function () use ($reconciliation): Reconciliation {
            $reconciliation = Reconciliation::query()
                ->with('items.inventoryBatch', 'items.inventoryItem', 'inventoryLocation')
                ->findOrFail($reconciliation->id);

            $this->guardCurrentBalances($reconciliation);

            $updatedRows = Reconciliation::query()
                ->whereKey($reconciliation->id)
                ->where('status', ReconciliationStatus::Draft)
                ->whereNotNull('approved_at')
                ->whereNull('rejected_at')
                ->update([
                    'status' => ReconciliationStatus::Posted,
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

            abort_unless($updatedRows === 1, 422, 'Only approved reconciliations can be posted.');

            $reconciliation = Reconciliation::query()
                ->with('items.inventoryBatch', 'items.inventoryItem', 'inventoryLocation')
                ->findOrFail($reconciliation->id);

            foreach ($reconciliation->items as $item) {
                $varianceQuantity = (float) $item->quantity_delta;

                if (abs($varianceQuantity) < 0.0005) {
                    continue;
                }

                $batch = $this->resolveBatch($reconciliation, $item);

                StockMovement::query()->create([
                    'tenant_id' => $reconciliation->tenant_id,
                    'branch_id' => $reconciliation->branch_id,
                    'inventory_location_id' => $reconciliation->inventory_location_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'inventory_batch_id' => $batch?->id,
                    'movement_type' => $varianceQuantity > 0
                        ? StockMovementType::AdjustmentGain
                        : StockMovementType::AdjustmentLoss,
                    'quantity' => $varianceQuantity,
                    'unit_cost' => $item->unit_cost,
                    'source_document_type' => Reconciliation::class,
                    'source_document_id' => $reconciliation->id,
                    'source_line_type' => ReconciliationItem::class,
                    'source_line_id' => $item->id,
                    'notes' => $item->notes ?? $reconciliation->reason,
                    'occurred_at' => $reconciliation->posted_at ?? now(),
                    'created_by' => Auth::id(),
                ]);
            }

            return $reconciliation;
        });
    }

    private function guardCurrentBalances(Reconciliation $reconciliation): void
    {
        $currentBalances = $this->inventoryStockLedger
            ->summarizeByLocation($reconciliation->branch_id)
            ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $reconciliation->inventory_location_id)
            ->mapWithKeys(static fn (array $balance): array => [
                $balance['inventory_item_id'] => $balance['quantity'],
            ]);

        $batchBalances = $this->inventoryStockLedger
            ->summarizeByBatch($reconciliation->branch_id)
            ->mapWithKeys(static fn (array $balance): array => [
                $balance['inventory_batch_id'] => $balance['quantity'],
            ]);

        foreach ($reconciliation->items as $item) {
            if ($item->expected_quantity !== null) {
                $currentQuantity = (float) ($currentBalances[$item->inventory_item_id] ?? 0.0);

                abort_unless(
                    abs($currentQuantity - (float) $item->expected_quantity) < 0.0005,
                    422,
                    'Stock moved after this reconciliation was recorded. Start a new reconciliation from the current balance.',
                );
            }

            if (
                (float) $item->quantity_delta < 0
                && is_string($item->inventory_batch_id)
                && $item->inventory_batch_id !== ''
            ) {
                abort_unless(
                    abs((float) $item->quantity_delta) <= (float) ($batchBalances[$item->inventory_batch_id] ?? 0.0),
                    422,
                    'A reconciliation loss cannot exceed the selected batch balance.',
                );
            }
        }
    }

    private function resolveBatch(Reconciliation $reconciliation, ReconciliationItem $item): ?InventoryBatch
    {
        if (is_string($item->inventory_batch_id) && $item->inventory_batch_id !== '') {
            return InventoryBatch::query()->find($item->inventory_batch_id);
        }

        if ((float) $item->quantity_delta <= 0) {
            return null;
        }

        return InventoryBatch::query()->create([
            'tenant_id' => $reconciliation->tenant_id,
            'branch_id' => $reconciliation->branch_id,
            'inventory_location_id' => $reconciliation->inventory_location_id,
            'inventory_item_id' => $item->inventory_item_id,
            'goods_receipt_item_id' => null,
            'batch_number' => $item->batch_number,
            'expiry_date' => $item->expiry_date,
            'unit_cost' => $item->unit_cost ?? 0,
            'quantity_received' => abs((float) $item->quantity_delta),
            'received_at' => $reconciliation->posted_at ?? now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
