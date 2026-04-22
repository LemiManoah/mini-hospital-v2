<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Clinical\CreateLabRequestItemConsumableDTO;
use App\Enums\InventoryLocationType;
use App\Enums\LabRequestItemStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\LabRequestItem;
use App\Models\LabRequestItemConsumable;
use App\Models\StockMovement;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordLabRequestItemConsumable
{
    public function __construct(
        private SyncLabRequestItemActualCost $syncLabRequestItemActualCost,
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public function handle(
        LabRequestItem $labRequestItem,
        CreateLabRequestItemConsumableDTO $data,
        ?string $staffId,
    ): LabRequestItemConsumable {
        return DB::transaction(function () use ($labRequestItem, $data, $staffId): LabRequestItemConsumable {
            $labRequestItem->loadMissing('request');
            $request = $labRequestItem->request;
            if (! $request instanceof \App\Models\LabRequest) {
                throw ValidationException::withMessages([
                    'lab_request_item_id' => 'The selected lab request item is not linked to a valid lab request.',
                ]);
            }

            $quantity = $data->quantity;
            $unitCost = $data->unitCost;

            $consumable = $labRequestItem->consumables()->create([
                'tenant_id' => $request->tenant_id,
                'facility_branch_id' => $request->facility_branch_id,
                'consumable_name' => $data->consumableName,
                'unit_label' => $data->unitLabel,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_cost' => $quantity * $unitCost,
                'notes' => $data->notes,
                'used_at' => $data->usedAt ?? now(),
                'recorded_by' => $staffId,
            ]);

            if ($data->inventoryItemId !== null) {
                $this->issueInventoryStock(
                    labRequestItem: $labRequestItem,
                    consumable: $consumable,
                    inventoryItemId: $data->inventoryItemId,
                    quantity: $quantity,
                    notes: $data->notes,
                );
            }

            if ($labRequestItem->status === LabRequestItemStatus::PENDING) {
                $labRequestItem->forceFill([
                    'status' => LabRequestItemStatus::IN_PROGRESS,
                ])->save();
            }

            $this->syncLabRequestItemActualCost->handle($labRequestItem);

            return $consumable->refresh();
        });
    }

    private function issueInventoryStock(
        LabRequestItem $labRequestItem,
        LabRequestItemConsumable $consumable,
        string $inventoryItemId,
        float $quantity,
        ?string $notes,
    ): void {
        $labRequestItem->loadMissing('request');
        $request = $labRequestItem->request;
        if (! $request instanceof \App\Models\LabRequest) {
            throw ValidationException::withMessages([
                'lab_request_item_id' => 'The selected lab request item is not linked to a valid lab request.',
            ]);
        }

        $laboratoryLocationIds = InventoryLocation::query()
            ->where('tenant_id', $request->tenant_id)
            ->where('branch_id', $request->facility_branch_id)
            ->where('type', InventoryLocationType::LABORATORY)
            ->where('is_active', true)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        if ($laboratoryLocationIds === []) {
            throw ValidationException::withMessages([
                'inventory_item_id' => 'No active laboratory stock location is configured for this branch.',
            ]);
        }

        $batchBalances = $this->inventoryStockLedger
            ->summarizeByBatch($request->facility_branch_id)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $laboratoryLocationIds, true))
            ->filter(static fn (array $balance): bool => $balance['inventory_item_id'] === $inventoryItemId)
            ->filter(static fn (array $balance): bool => $balance['quantity'] > 0)
            ->keyBy('inventory_batch_id');

        /** @var Collection<string, InventoryBatch> $candidateBatches */
        $candidateBatches = InventoryBatch::query()
            ->whereIn('inventory_location_id', $laboratoryLocationIds)
            ->where('inventory_item_id', $inventoryItemId)
            ->lockForUpdate()
            ->get()
            ->filter(static fn (InventoryBatch $batch): bool => $batchBalances->has($batch->id))
            ->sortBy([
                static fn (InventoryBatch $batch): int => $batch->expiry_date === null ? 1 : 0,
                static fn (InventoryBatch $batch): string => $batch->expiry_date?->toDateString() ?? '9999-12-31',
                static fn (InventoryBatch $batch): string => $batch->received_at->toIso8601String(),
            ]);

        $availableQuantity = $candidateBatches->sum(
            static fn (InventoryBatch $batch): float => (float) ($batchBalances->get($batch->id)['quantity'] ?? 0.0),
        );

        if ($availableQuantity + 0.0005 < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'The selected laboratory stock item does not have enough available quantity.',
            ]);
        }

        $remainingQuantity = $quantity;

        foreach ($candidateBatches as $batch) {
            if ($remainingQuantity <= 0.0005) {
                break;
            }

            $availableBatchQuantity = (float) ($batchBalances->get($batch->id)['quantity'] ?? 0.0);

            if ($availableBatchQuantity <= 0) {
                continue;
            }

            $issuedQuantity = min($remainingQuantity, $availableBatchQuantity);

            StockMovement::query()->create([
                'tenant_id' => $request->tenant_id,
                'branch_id' => $request->facility_branch_id,
                'inventory_location_id' => $batch->inventory_location_id,
                'inventory_item_id' => $inventoryItemId,
                'inventory_batch_id' => $batch->id,
                'movement_type' => StockMovementType::Issue,
                'quantity' => -1 * $issuedQuantity,
                'unit_cost' => $batch->unit_cost,
                'source_document_type' => LabRequestItemConsumable::class,
                'source_document_id' => $consumable->id,
                'source_line_type' => LabRequestItem::class,
                'source_line_id' => $labRequestItem->id,
                'notes' => $notes,
                'occurred_at' => $consumable->used_at,
                'created_by' => Auth::id(),
            ]);

            $remainingQuantity -= $issuedQuantity;
        }
    }
}
