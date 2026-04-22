<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Pharmacy\PostDispenseDTO;
use App\Data\Pharmacy\PostDispenseItemDTO;
use App\Enums\DispensingRecordStatus;
use App\Enums\StockMovementType;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItem;
use App\Models\InventoryBatch;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryStockLedger;
use App\Support\PrescriptionDispenseProgress;
use App\Support\PrescriptionDispenseStatusResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-type AvailableBatchData array{
 *     inventory_batch_id: string,
 *     inventory_item_id: string,
 *     batch_number: string|null,
 *     expiry_date: string|null,
 *     quantity: float,
 *     batch: InventoryBatch
 * }
 * @phpstan-type ResolvedAllocation array{batch: InventoryBatch, quantity: float}
 */
final readonly class PostDispense
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
        private TenantGeneralSettings $tenantGeneralSettings,
        private PrescriptionDispenseProgress $prescriptionDispenseProgress,
        private PrescriptionDispenseStatusResolver $statusResolver,
    ) {}

    public function handle(DispensingRecord $dispensingRecord, PostDispenseDTO $data): DispensingRecord
    {
        return DB::transaction(function () use ($dispensingRecord, $data): DispensingRecord {
            $dispensingRecord = DispensingRecord::query()
                ->with([
                    'items.prescriptionItem',
                    'items.inventoryItem',
                    'prescription.items',
                ])
                ->lockForUpdate()
                ->findOrFail($dispensingRecord->id);

            if ($dispensingRecord->status !== DispensingRecordStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'dispensing_record' => 'Only draft dispensing records can be posted.',
                ]);
            }

            $batchTrackingEnabled = $this->tenantGeneralSettings->boolean(
                $dispensingRecord->tenant_id,
                'enable_batch_tracking_when_dispensing',
            );

            /** @var Collection<string, PostDispenseItemDTO> $payloadByItem */
            $payloadByItem = collect($data->items)
                ->mapWithKeys(static fn (PostDispenseItemDTO $item): array => [
                    $item->dispensingRecordItemId => $item,
                ]);

            $availableBatches = $this->availableBatches($dispensingRecord);

            /** @var Collection<string, float> $availableQuantities */
            $availableQuantities = $availableBatches
                ->mapWithKeys(static fn (array $batch): array => [
                    $batch['inventory_batch_id'] => $batch['quantity'],
                ]);

            /** @var Collection<string, array{dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at: Carbon|null, external_pharmacy: bool}> $postedLineSummaries */
            $postedLineSummaries = $this->prescriptionDispenseProgress->postedLineSummaries(
                $dispensingRecord->prescription_id,
                $dispensingRecord->id,
            );

            $hasPostableOutcome = false;

            foreach ($dispensingRecord->items as $recordItem) {
                $prescriptionItem = $recordItem->prescriptionItem;
                if (! $prescriptionItem instanceof PrescriptionItem) {
                    throw ValidationException::withMessages([
                        'items' => 'One of the linked prescription lines could not be resolved.',
                    ]);
                }

                $dispensedQuantity = round((float) $recordItem->dispensed_quantity, 3);
                $alreadyCoveredQuantity = (float) ($postedLineSummaries->get($recordItem->prescription_item_id)['covered_quantity'] ?? 0.0);
                $remainingQuantity = max(
                    0,
                    round((float) $prescriptionItem->quantity - $alreadyCoveredQuantity, 3),
                );

                if ($dispensedQuantity > $remainingQuantity + 0.0005) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'The drafted dispense quantity for %s exceeds the remaining quantity on the prescription.',
                            $recordItem->inventoryItem->generic_name ?? $recordItem->inventoryItem->name ?? 'one medication line',
                        ),
                    ]);
                }

                if ($dispensedQuantity > 0) {
                    $hasPostableOutcome = true;

                    $allocations = $batchTrackingEnabled
                        ? $this->manualAllocations(
                            $recordItem,
                            $payloadByItem->get($recordItem->id),
                            $availableBatches,
                            $availableQuantities,
                        )
                        : $this->autoAllocate(
                            $recordItem,
                            $dispensedQuantity,
                            $availableBatches,
                            $availableQuantities,
                        );

                    foreach ($allocations as $allocation) {
                        $batch = $allocation['batch'];
                        $quantity = $allocation['quantity'];

                        $recordItem->allocations()->create([
                            'inventory_batch_id' => $batch->id,
                            'quantity' => $quantity,
                            'unit_cost_snapshot' => $batch->unit_cost,
                            'batch_number_snapshot' => $batch->batch_number,
                            'expiry_date_snapshot' => $batch->expiry_date,
                        ]);

                        StockMovement::query()->create([
                            'tenant_id' => $dispensingRecord->tenant_id,
                            'branch_id' => $dispensingRecord->branch_id,
                            'inventory_location_id' => $dispensingRecord->inventory_location_id,
                            'inventory_item_id' => $recordItem->inventory_item_id,
                            'inventory_batch_id' => $batch->id,
                            'movement_type' => StockMovementType::Dispense,
                            'quantity' => -1 * $quantity,
                            'unit_cost' => $batch->unit_cost,
                            'source_document_type' => DispensingRecord::class,
                            'source_document_id' => $dispensingRecord->id,
                            'source_line_type' => DispensingRecordItem::class,
                            'source_line_id' => $recordItem->id,
                            'notes' => $recordItem->notes ?? $dispensingRecord->notes,
                            'occurred_at' => $dispensingRecord->dispensed_at ?? now(),
                            'created_by' => Auth::id(),
                        ]);

                        $availableQuantities->put(
                            $batch->id,
                            max(0, (float) $availableQuantities->get($batch->id, 0.0) - $quantity),
                        );
                    }
                }

                if ($recordItem->external_pharmacy) {
                    $hasPostableOutcome = true;
                }
            }

            if (! $hasPostableOutcome) {
                throw ValidationException::withMessages([
                    'items' => 'This dispense record does not have any quantities or external outcomes to post.',
                ]);
            }

            $dispensingRecord->update([
                'status' => DispensingRecordStatus::POSTED,
            ]);

            $this->syncPrescriptionStatuses($dispensingRecord->prescription_id);

            return $dispensingRecord->refresh()->load([
                'visit.patient',
                'prescription.prescribedBy',
                'inventoryLocation',
                'dispensedBy.staff',
                'items.prescriptionItem.inventoryItem',
                'items.inventoryItem',
                'items.substitutionInventoryItem',
                'items.allocations.inventoryBatch',
            ]);
        });
    }

    /**
     * @return Collection<string, AvailableBatchData>
     */
    private function availableBatches(DispensingRecord $dispensingRecord): Collection
    {
        $balances = $this->inventoryStockLedger
            ->summarizeByBatch($dispensingRecord->branch_id)
            ->filter(
                static fn (array $balance): bool => $balance['inventory_location_id'] === $dispensingRecord->inventory_location_id
                    && $balance['quantity'] > 0
            )
            ->values();

        $batches = InventoryBatch::query()
            ->whereIn('id', $balances->pluck('inventory_batch_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        /** @var Collection<string, AvailableBatchData> $availableBatches */
        $availableBatches = $balances
            ->map(function (array $balance) use ($batches): ?array {
                $batch = $batches->get($balance['inventory_batch_id']);

                if (! $batch instanceof InventoryBatch) {
                    return null;
                }

                if ($batch->expiry_date !== null && $batch->expiry_date->startOfDay()->isBefore(today())) {
                    return null;
                }

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'batch' => $batch,
                ];
            })
            ->filter(static fn (?array $batch): bool => is_array($batch))
            ->mapWithKeys(static fn (array $batch): array => [$batch['inventory_batch_id'] => $batch]);

        return $availableBatches;
    }

    /**
     * @param  Collection<string, AvailableBatchData>  $availableBatches
     * @param  Collection<string, float>  $availableQuantities
     * @return list<ResolvedAllocation>
     */
    private function manualAllocations(
        DispensingRecordItem $recordItem,
        ?PostDispenseItemDTO $payload,
        Collection $availableBatches,
        Collection $availableQuantities,
    ): array {
        $allocations = $payload === null ? [] : $payload->allocations;

        if ($allocations === []) {
            throw ValidationException::withMessages([
                sprintf('items.%s.allocations', $recordItem->id) => 'Select one or more pharmacy batches before posting this dispense.',
            ]);
        }

        $remainingByBatch = $availableQuantities->all();
        /** @var list<ResolvedAllocation> $resolvedAllocations */
        $resolvedAllocations = [];
        $allocationTotal = 0.0;

        foreach ($allocations as $allocation) {
            $batchData = $availableBatches->get($allocation->inventoryBatchId);
            if (! is_array($batchData)) {
                throw ValidationException::withMessages([
                    sprintf('items.%s.allocations', $recordItem->id) => 'One of the selected pharmacy batches is no longer available.',
                ]);
            }

            if ($batchData['inventory_item_id'] !== $recordItem->inventory_item_id) {
                throw ValidationException::withMessages([
                    sprintf('items.%s.allocations', $recordItem->id) => 'Selected batches must match the medication line being dispensed.',
                ]);
            }

            $allocationQuantity = round($allocation->quantity, 3);
            $availableQuantity = (float) ($remainingByBatch[$allocation->inventoryBatchId] ?? 0.0);

            if ($allocationQuantity <= 0 || $allocationQuantity > $availableQuantity + 0.0005) {
                throw ValidationException::withMessages([
                    sprintf('items.%s.allocations', $recordItem->id) => 'One of the selected pharmacy batches does not have enough available stock.',
                ]);
            }

            $remainingByBatch[$allocation->inventoryBatchId] = $availableQuantity - $allocationQuantity;
            $allocationTotal += $allocationQuantity;
            $resolvedAllocations[] = [
                /** @var InventoryBatch $batch */
                'batch' => $batchData['batch'],
                'quantity' => $allocationQuantity,
            ];
        }

        if (abs($allocationTotal - round((float) $recordItem->dispensed_quantity, 3)) > 0.0005) {
            throw ValidationException::withMessages([
                sprintf('items.%s.allocations', $recordItem->id) => 'Allocated batch quantities must add up to the dispensed quantity.',
            ]);
        }

        return $resolvedAllocations;
    }

    /**
     * @param  Collection<string, AvailableBatchData>  $availableBatches
     * @param  Collection<string, float>  $availableQuantities
     * @return list<ResolvedAllocation>
     */
    private function autoAllocate(
        DispensingRecordItem $recordItem,
        float $dispensedQuantity,
        Collection $availableBatches,
        Collection $availableQuantities,
    ): array {
        $remaining = $dispensedQuantity;
        /** @var list<ResolvedAllocation> $allocations */
        $allocations = [];

        /** @var Collection<int, AvailableBatchData> $candidateBatches */
        $candidateBatches = $availableBatches
            ->filter(
                static fn (array $batch): bool => $batch['inventory_item_id'] === $recordItem->inventory_item_id
                    && (float) $availableQuantities->get($batch['inventory_batch_id'], 0.0) > 0
            )
            ->sortBy(
                static fn (array $batch): string => sprintf(
                    '%s|%s',
                    $batch['expiry_date'] ?? '9999-12-31',
                    $batch['batch_number'] ?? 'ZZZ',
                )
            )
            ->values();

        foreach ($candidateBatches as $batch) {
            $availableQuantity = (float) $availableQuantities->get($batch['inventory_batch_id'], 0.0);
            if ($availableQuantity <= 0) {
                continue;
            }

            $allocatedQuantity = min($remaining, $availableQuantity);

            if ($allocatedQuantity <= 0) {
                continue;
            }

            $allocations[] = [
                'batch' => $batch['batch'],
                'quantity' => round($allocatedQuantity, 3),
            ];

            $remaining = round($remaining - $allocatedQuantity, 3);

            if ($remaining <= 0.0005) {
                break;
            }
        }

        if ($remaining > 0.0005) {
            throw ValidationException::withMessages([
                'items' => sprintf(
                    'There is not enough available pharmacy stock to post %s.',
                    $recordItem->inventoryItem->generic_name ?? $recordItem->inventoryItem->name ?? 'this medication line',
                ),
            ]);
        }

        return $allocations;
    }

    private function syncPrescriptionStatuses(string $prescriptionId): void
    {
        $prescription = Prescription::query()
            ->with('items')
            ->lockForUpdate()
            ->findOrFail($prescriptionId);

        /** @var Collection<string, array{dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at: Carbon|null, external_pharmacy: bool}> $postedLineSummaries */
        $postedLineSummaries = $this->prescriptionDispenseProgress->postedLineSummaries($prescription->id);

        foreach ($prescription->items as $prescriptionItem) {
            $summary = $postedLineSummaries->get($prescriptionItem->id);
            $dispensedQuantity = (float) ($summary['dispensed_quantity'] ?? 0.0);
            $latestDispensedAt = $summary['latest_dispensed_at'] ?? null;
            $isExternalPharmacy = (bool) ($summary['external_pharmacy'] ?? false);
            $coveredQuantity = (float) ($summary['covered_quantity'] ?? 0.0);

            $prescriptionItem->update([
                'status' => $this->statusResolver->itemStatus(
                    $coveredQuantity,
                    round((float) $prescriptionItem->quantity, 3),
                    $isExternalPharmacy,
                    $prescriptionItem->status,
                ),
                'dispensed_at' => $latestDispensedAt?->toDateTimeString(),
                'is_external_pharmacy' => $isExternalPharmacy,
            ]);
        }

        $prescription->load('items');

        $prescription->update([
            'status' => $this->statusResolver->prescriptionStatus($prescription),
        ]);
    }
}
