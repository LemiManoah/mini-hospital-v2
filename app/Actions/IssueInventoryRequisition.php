<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryRequisition;
use App\Models\InventoryRequisitionItem;
use App\Models\StockMovement;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class IssueInventoryRequisition
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    /**
     * @param  list<array{
     *     inventory_requisition_item_id: string,
     *     issue_quantity: float|int|string,
     *     notes?: string|null,
     *     allocations?: list<array{inventory_batch_id: string, quantity: float|int|string}>
     * }>  $items
     */
    public function handle(InventoryRequisition $requisition, array $items, ?string $issuedNotes = null): InventoryRequisition
    {
        return DB::transaction(function () use ($requisition, $items, $issuedNotes): InventoryRequisition {
            $requisition = InventoryRequisition::query()
                ->with('items.inventoryItem')
                ->lockForUpdate()
                ->findOrFail($requisition->id);

            abort_unless(
                $requisition->canBeIssued(),
                422,
                'Only approved requisitions can be issued.',
            );

            $allocations = collect($items)
                ->filter(static fn (array $item): bool => (float) $item['issue_quantity'] > 0);

            abort_if($allocations->isEmpty(), 422, 'Issue at least one approved quantity.');

            /** @var list<string> $batchIds */
            $batchIds = $allocations
                ->flatMap(static fn (array $item): array => collect($item['allocations'] ?? [])
                    ->pluck('inventory_batch_id')
                    ->filter(static fn (mixed $batchId): bool => is_string($batchId) && $batchId !== '')
                    ->values()
                    ->all())
                ->unique()
                ->values();

            /** @var Collection<string, InventoryBatch> $sourceBatches */
            $sourceBatches = InventoryBatch::query()
                ->whereIn('id', $batchIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $batchBalances = $this->inventoryStockLedger
                ->summarizeByBatch($requisition->branch_id)
                ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $requisition->source_inventory_location_id)
                ->mapWithKeys(static fn (array $balance): array => [
                    $balance['inventory_batch_id'] => $balance['quantity'],
                ]);

            foreach ($allocations as $payload) {
                $line = $requisition->items->firstWhere('id', $payload['inventory_requisition_item_id']);
                abort_unless($line instanceof InventoryRequisitionItem, 422, 'One of the requisition lines is invalid.');

                $issueQuantity = (float) $payload['issue_quantity'];
                $allocationTotal = collect($payload['allocations'] ?? [])
                    ->sum(static fn (array $allocation): float => (float) $allocation['quantity']);

                abort_unless(
                    abs($allocationTotal - $issueQuantity) < 0.0005,
                    422,
                    'Allocated batch quantities must add up to the issue quantity.',
                );
                abort_unless(
                    $issueQuantity <= $line->remainingApprovedQuantity(),
                    422,
                    'The issue quantity cannot exceed the remaining approved quantity.',
                );

                foreach ($payload['allocations'] ?? [] as $allocation) {
                    $batch = $sourceBatches->get($allocation['inventory_batch_id']);
                    abort_unless($batch instanceof InventoryBatch, 422, 'One of the selected source batches is invalid.');
                    abort_unless(
                        $batch->inventory_location_id === $requisition->source_inventory_location_id
                        && $batch->inventory_item_id === $line->inventory_item_id,
                        422,
                        'Selected source batches must belong to the requisition source location and item.',
                    );
                    abort_unless(
                        (float) $allocation['quantity'] <= (float) ($batchBalances[$batch->id] ?? 0.0),
                        422,
                        'One of the selected source batches does not have enough available stock.',
                    );
                }

                foreach ($payload['allocations'] ?? [] as $allocation) {
                    $batch = $sourceBatches->get($allocation['inventory_batch_id']);
                    if (! $batch instanceof InventoryBatch) {
                        continue;
                    }

                    $quantity = (float) $allocation['quantity'];
                    $destinationBatch = $this->resolveDestinationBatch($requisition, $batch, $quantity);

                    StockMovement::query()->create([
                        'tenant_id' => $requisition->tenant_id,
                        'branch_id' => $requisition->branch_id,
                        'inventory_location_id' => $requisition->source_inventory_location_id,
                        'inventory_item_id' => $line->inventory_item_id,
                        'inventory_batch_id' => $batch->id,
                        'movement_type' => StockMovementType::RequisitionOut,
                        'quantity' => -1 * $quantity,
                        'unit_cost' => $batch->unit_cost,
                        'source_document_type' => InventoryRequisition::class,
                        'source_document_id' => $requisition->id,
                        'source_line_type' => InventoryRequisitionItem::class,
                        'source_line_id' => $line->id,
                        'notes' => ($payload['notes'] ?? '') !== '' ? $payload['notes'] : $requisition->notes,
                        'occurred_at' => now(),
                        'created_by' => Auth::id(),
                    ]);

                    StockMovement::query()->create([
                        'tenant_id' => $requisition->tenant_id,
                        'branch_id' => $requisition->branch_id,
                        'inventory_location_id' => $requisition->destination_inventory_location_id,
                        'inventory_item_id' => $line->inventory_item_id,
                        'inventory_batch_id' => $destinationBatch->id,
                        'movement_type' => StockMovementType::RequisitionIn,
                        'quantity' => $quantity,
                        'unit_cost' => $batch->unit_cost,
                        'source_document_type' => InventoryRequisition::class,
                        'source_document_id' => $requisition->id,
                        'source_line_type' => InventoryRequisitionItem::class,
                        'source_line_id' => $line->id,
                        'notes' => ($payload['notes'] ?? '') !== '' ? $payload['notes'] : $requisition->notes,
                        'occurred_at' => now(),
                        'created_by' => Auth::id(),
                    ]);

                    $batchBalances[$batch->id] = (float) ($batchBalances[$batch->id] ?? 0.0) - $quantity;
                }

                $line->update([
                    'issued_quantity' => (float) $line->issued_quantity + $issueQuantity,
                ]);
            }

            $requisition->refresh()->load('items');
            $fullyIssued = $requisition->items->every(
                static fn (InventoryRequisitionItem $item): bool => (float) $item->issued_quantity >= (float) $item->approved_quantity
            );

            $requisition->update([
                'status' => $fullyIssued
                    ? InventoryRequisitionStatus::Fulfilled
                    : InventoryRequisitionStatus::PartiallyIssued,
                'issued_by' => Auth::id(),
                'issued_at' => now(),
                'issued_notes' => $issuedNotes !== '' ? $issuedNotes : null,
                'updated_by' => Auth::id(),
            ]);

            return $requisition->refresh()->load([
                'fulfillingLocation',
                'requestingLocation',
                'items.inventoryItem',
            ]);
        });
    }

    private function resolveDestinationBatch(
        InventoryRequisition $requisition,
        InventoryBatch $sourceBatch,
        float $quantity,
    ): InventoryBatch {
        $destinationBatch = InventoryBatch::query()
            ->where('tenant_id', $requisition->tenant_id)
            ->where('branch_id', $requisition->branch_id)
            ->where('inventory_location_id', $requisition->destination_inventory_location_id)
            ->where('inventory_item_id', $sourceBatch->inventory_item_id)
            ->whereNull('goods_receipt_item_id')
            ->where('batch_number', $sourceBatch->batch_number)
            ->where('expiry_date', $sourceBatch->expiry_date)
            ->where('unit_cost', $sourceBatch->unit_cost)
            ->first();

        if ($destinationBatch instanceof InventoryBatch) {
            $destinationBatch->update([
                'quantity_received' => (float) $destinationBatch->quantity_received + $quantity,
                'updated_by' => Auth::id(),
            ]);

            return $destinationBatch;
        }

        return InventoryBatch::query()->create([
            'tenant_id' => $requisition->tenant_id,
            'branch_id' => $requisition->branch_id,
            'inventory_location_id' => $requisition->destination_inventory_location_id,
            'inventory_item_id' => $sourceBatch->inventory_item_id,
            'goods_receipt_item_id' => null,
            'batch_number' => $sourceBatch->batch_number,
            'expiry_date' => $sourceBatch->expiry_date,
            'unit_cost' => $sourceBatch->unit_cost,
            'quantity_received' => $quantity,
            'received_at' => now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
