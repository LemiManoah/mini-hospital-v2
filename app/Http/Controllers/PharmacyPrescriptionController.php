<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DispensingRecord;
use App\Models\InventoryLocation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use App\Support\PrescriptionDispenseProgress;
use App\Support\PrescriptionQueueQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyPrescriptionController implements HasMiddleware
{
    public function __construct(
        private PrescriptionQueueQuery $prescriptionQueueQuery,
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryStockLedger $inventoryStockLedger,
        private PrescriptionDispenseProgress $prescriptionDispenseProgress,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_prescriptions.view', only: ['show']),
        ];
    }

    public function show(Request $request, Prescription $prescription): Response
    {
        $record = $this->prescriptionQueueQuery->findForPharmacy($prescription->id);

        abort_unless($record instanceof Prescription, 404);

        $branchId = BranchContext::getActiveBranchId();
        $locations = $this->dispensingLocations();
        $stockBalances = is_string($branchId) && $branchId !== ''
            ? $this->itemBalancesForLocations($branchId, $locations)
            : collect();
        $progress = $this->prescriptionDispenseProgress->postedLineSummaries($record->id);
        $visit = $record->visit;
        $patient = $visit?->patient;

        /** @var Collection<string, array{dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at: Carbon|null, external_pharmacy: bool}> $progress */
        $serializedItems = $record->items
            ->map(fn (PrescriptionItem $item): array => $this->serializePrescriptionItem($item, $stockBalances, $progress))
            ->values()
            ->all();

        return Inertia::render('pharmacy/prescriptions/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'prescription' => [
                'id' => $record->id,
                'visit_id' => $record->visit_id,
                'visit_number' => $visit?->visit_number,
                'prescription_date' => $record->prescription_date?->toISOString(),
                'status' => $record->status?->value,
                'status_label' => $record->status?->label(),
                'primary_diagnosis' => $record->primary_diagnosis,
                'pharmacy_notes' => $record->pharmacy_notes,
                'patient' => $patient === null ? null : [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => mb_trim(sprintf(
                        '%s %s',
                        $patient->first_name,
                        $patient->last_name,
                    )),
                    'gender' => $patient->gender,
                    'phone_number' => $patient->phone_number,
                ],
                'prescribed_by' => $record->prescribedBy === null ? null : [
                    'id' => $record->prescribedBy->id,
                    'name' => mb_trim(sprintf(
                        '%s %s',
                        $record->prescribedBy->first_name,
                        $record->prescribedBy->last_name,
                    )),
                ],
                'items' => $serializedItems,
                'dispensing_records' => $record->dispensingRecords
                    ->map(fn (DispensingRecord $dispense): array => $this->serializeDispensingRecord($dispense))
                    ->values()
                    ->all(),
            ],
            'dispensingLocations' => $locations
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                    'is_dispensing_point' => $location->is_dispensing_point,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return Collection<int, InventoryLocation>
     */
    private function dispensingLocations(): Collection
    {
        $branchId = BranchContext::getActiveBranchId();

        $locations = $this->inventoryLocationAccess->accessibleLocations(
            Auth::user(),
            $branchId,
            ['pharmacy'],
        );

        $dispensingPoints = $locations
            ->filter(static fn (InventoryLocation $location): bool => $location->is_dispensing_point)
            ->values();

        return $dispensingPoints->isNotEmpty() ? $dispensingPoints : $locations->values();
    }

    /**
     * @param  Collection<int, InventoryLocation>  $locations
     * @return Collection<string, float>
     */
    private function itemBalancesForLocations(string $branchId, Collection $locations): Collection
    {
        $locationIds = $locations
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        if ($locationIds === []) {
            /** @var Collection<string, float> $empty */
            $empty = collect();

            return $empty;
        }

        /** @var Collection<string, float> $balances */
        $balances = $this->inventoryStockLedger
            ->summarizeByLocation($branchId)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $rows): float => $rows->reduce(
                static fn (float $carry, array $row): float => $carry + $row['quantity'],
                0.0,
            ));

        return $balances;
    }

    /**
     * @param  Collection<string, float>  $stockBalances
     * @param  Collection<string, array{dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at: Carbon|null, external_pharmacy: bool}>  $progress
     * @return array<string, mixed>
     */
    private function serializePrescriptionItem(
        PrescriptionItem $item,
        Collection $stockBalances,
        Collection $progress,
    ): array {
        $itemProgress = $progress->get($item->id);
        $requestedQuantity = round((float) $item->quantity, 3);
        $coveredQuantity = min($requestedQuantity, round($itemProgress['covered_quantity'] ?? 0.0, 3));
        $locallyDispensedQuantity = min($requestedQuantity, round($itemProgress['dispensed_quantity'] ?? 0.0, 3));
        $remainingQuantity = max(0.0, round($requestedQuantity - $coveredQuantity, 3));
        $availableQuantity = round((float) ($stockBalances->get($item->inventory_item_id) ?? 0), 3);
        $stockStatus = match (true) {
            $remainingQuantity <= 0 => 'ready',
            $availableQuantity >= $remainingQuantity && $remainingQuantity > 0 => 'ready',
            $availableQuantity > 0 => 'partial',
            default => 'out_of_stock',
        };
        $inventoryItem = $item->inventoryItem;
        $dosageForm = $inventoryItem?->dosage_form;

        return [
            'id' => $item->id,
            'inventory_item_id' => $item->inventory_item_id,
            'item_name' => $inventoryItem?->name,
            'generic_name' => $inventoryItem?->generic_name,
            'brand_name' => $inventoryItem?->brand_name,
            'strength' => $inventoryItem?->strength,
            'dosage_form' => $dosageForm?->value,
            'dosage' => $item->dosage,
            'frequency' => $item->frequency,
            'route' => $item->route,
            'duration_days' => $item->duration_days,
            'quantity' => $requestedQuantity,
            'remaining_quantity' => $remainingQuantity,
            'covered_quantity' => $coveredQuantity,
            'locally_dispensed_quantity' => $locallyDispensedQuantity,
            'instructions' => $item->instructions,
            'status' => $item->status?->value,
            'status_label' => $item->status?->label(),
            'dispensed_at' => $item->dispensed_at?->toISOString(),
            'external_pharmacy' => $item->is_external_pharmacy,
            'available_quantity' => $availableQuantity,
            'stock_status' => $stockStatus,
            'stock_status_label' => match ($stockStatus) {
                'ready' => $remainingQuantity <= 0 ? 'Handled' : 'Ready',
                'partial' => 'Partial Stock',
                default => 'Out Of Stock',
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDispensingRecord(DispensingRecord $dispense): array
    {
        $inventoryLocation = $dispense->inventoryLocation;
        $dispensedBy = $dispense->dispensedBy;
        $staff = $dispensedBy?->staff;

        return [
            'id' => $dispense->id,
            'dispense_number' => $dispense->dispense_number,
            'status' => $dispense->status->value,
            'status_label' => $dispense->status->label(),
            'dispensed_at' => $dispense->dispensed_at->toISOString(),
            'inventory_location' => $inventoryLocation === null ? null : [
                'id' => $inventoryLocation->id,
                'name' => $inventoryLocation->name,
                'location_code' => $inventoryLocation->location_code,
            ],
            'dispensed_by' => $staff === null
                ? ($dispensedBy?->email)
                : mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name)),
        ];
    }
}
