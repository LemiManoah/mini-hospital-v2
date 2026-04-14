<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InventoryLocation;
use App\Models\Prescription;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use App\Support\PrescriptionQueueQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['show']),
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

        $serializedItems = $record->items
            ->map(function ($item) use ($stockBalances): array {
                $availableQuantity = round((float) ($stockBalances->get((string) $item->inventory_item_id) ?? 0), 3);
                $requestedQuantity = round((float) $item->quantity, 3);
                $stockStatus = match (true) {
                    $availableQuantity >= $requestedQuantity && $requestedQuantity > 0 => 'ready',
                    $availableQuantity > 0 => 'partial',
                    default => 'out_of_stock',
                };

                return [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $item->inventoryItem?->name,
                    'generic_name' => $item->inventoryItem?->generic_name,
                    'brand_name' => $item->inventoryItem?->brand_name,
                    'strength' => $item->inventoryItem?->strength,
                    'dosage_form' => $item->inventoryItem?->dosage_form?->value ?? $item->inventoryItem?->dosage_form,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'route' => $item->route,
                    'duration_days' => $item->duration_days,
                    'quantity' => $requestedQuantity,
                    'instructions' => $item->instructions,
                    'status' => $item->status?->value,
                    'status_label' => $item->status?->label(),
                    'dispensed_at' => $item->dispensed_at?->toISOString(),
                    'available_quantity' => $availableQuantity,
                    'stock_status' => $stockStatus,
                    'stock_status_label' => match ($stockStatus) {
                        'ready' => 'Ready',
                        'partial' => 'Partial Stock',
                        default => 'Out Of Stock',
                    },
                ];
            })
            ->values()
            ->all();

        return Inertia::render('pharmacy/prescriptions/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'prescription' => [
                'id' => $record->id,
                'visit_id' => $record->visit_id,
                'visit_number' => $record->visit?->visit_number,
                'prescription_date' => $record->prescription_date?->toISOString(),
                'status' => $record->status?->value,
                'status_label' => $record->status?->label(),
                'primary_diagnosis' => $record->primary_diagnosis,
                'pharmacy_notes' => $record->pharmacy_notes,
                'patient' => $record->visit?->patient === null ? null : [
                    'id' => $record->visit->patient->id,
                    'patient_number' => $record->visit->patient->patient_number,
                    'full_name' => mb_trim(sprintf(
                        '%s %s',
                        $record->visit->patient->first_name,
                        $record->visit->patient->last_name,
                    )),
                    'gender' => $record->visit->patient->gender,
                    'phone_number' => $record->visit->patient->phone_number,
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
                    ->map(static fn ($dispense): array => [
                        'id' => $dispense->id,
                        'dispense_number' => $dispense->dispense_number,
                        'status' => $dispense->status?->value,
                        'status_label' => $dispense->status?->label(),
                        'dispensed_at' => $dispense->dispensed_at?->toISOString(),
                        'inventory_location' => $dispense->inventoryLocation === null ? null : [
                            'id' => $dispense->inventoryLocation->id,
                            'name' => $dispense->inventoryLocation->name,
                            'location_code' => $dispense->inventoryLocation->location_code,
                        ],
                        'dispensed_by' => $dispense->dispensedBy?->staff === null
                            ? ($dispense->dispensedBy?->email)
                            : mb_trim(sprintf(
                                '%s %s',
                                $dispense->dispensedBy->staff->first_name,
                                $dispense->dispensedBy->staff->last_name,
                            )),
                    ])
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
            return collect();
        }

        return $this->inventoryStockLedger
            ->summarizeByLocation($branchId)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $rows): float => (float) $rows->sum('quantity'));
    }
}
