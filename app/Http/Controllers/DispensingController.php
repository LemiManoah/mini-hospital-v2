<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDispensingRecord;
use App\Actions\DispensePrescription;
use App\Actions\PostDispense;
use App\Enums\DispensingRecordStatus;
use App\Http\Requests\DispensePrescriptionRequest;
use App\Http\Requests\PostDispenseRequest;
use App\Http\Requests\StoreDispenseRequest;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItem;
use App\Models\DispensingRecordItemAllocation;
use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use App\Support\PrescriptionQueueQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DispensingController implements HasMiddleware
{
    public function __construct(
        private PrescriptionQueueQuery $prescriptionQueueQuery,
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryStockLedger $inventoryStockLedger,
        private TenantGeneralSettings $tenantGeneralSettings,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['create', 'store', 'show', 'post', 'dispense']),
        ];
    }

    public function create(Request $request, Prescription $prescription): Response
    {
        $record = $this->prescriptionQueueQuery->findForPharmacy($prescription->id);

        abort_unless($record instanceof Prescription, 404);

        $branchId = BranchContext::getActiveBranchId();
        $locations = $this->dispensingLocations();
        $stockBalances = is_string($branchId) && $branchId !== ''
            ? $this->itemBalancesForLocations($branchId, $locations)
            : collect();
        $visit = $record->visit;
        $patient = $visit?->patient;

        return Inertia::render('pharmacy/dispenses/create', [
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
                'items' => $record->items
                    ->map(fn (PrescriptionItem $item): array => $this->serializeCreatePrescriptionItem($item, $stockBalances))
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
            'defaults' => [
                'inventory_location_id' => $locations->first()?->id,
                'dispensed_at' => now()->format('Y-m-d\TH:i'),
            ],
            'pharmacyPolicy' => $this->pharmacyPolicy($locations->first()?->tenant_id),
        ]);
    }

    public function store(
        StoreDispenseRequest $request,
        Prescription $prescription,
        CreateDispensingRecord $action,
    ): RedirectResponse {
        $record = $this->prescriptionQueueQuery->findForPharmacy($prescription->id);

        abort_unless($record instanceof Prescription, 404);

        $validated = $request->validated();
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($validated['items'] ?? null) ? $validated['items'] : [];
        unset($validated['items']);

        $dispensingRecord = $action->handle($record, $validated, $items);

        return to_route('pharmacy.dispenses.show', ['dispensingRecord' => $dispensingRecord])
            ->with('success', 'Dispensing record created. Post it when you are ready to update stock.');
    }

    public function post(
        PostDispenseRequest $request,
        DispensingRecord $dispensingRecord,
        PostDispense $action,
    ): RedirectResponse {
        abort_unless($dispensingRecord->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless(
            $this->inventoryLocationAccess->canAccessLocationForTypes(
                Auth::user(),
                $dispensingRecord->inventory_location_id,
                ['pharmacy'],
                $dispensingRecord->branch_id,
            ),
            403,
            'You do not have access to this dispensing record.',
        );

        $validated = $request->validated();

        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($validated['items'] ?? null) ? $validated['items'] : [];

        $postedRecord = $action->handle($dispensingRecord, $items);

        return to_route('pharmacy.dispenses.show', ['dispensingRecord' => $postedRecord])
            ->with('success', 'Dispense posted successfully and pharmacy stock has been updated.');
    }

    public function dispense(
        DispensePrescriptionRequest $request,
        Prescription $prescription,
        DispensePrescription $action,
    ): RedirectResponse {
        $record = $this->prescriptionQueueQuery->findForPharmacy($prescription->id);

        abort_unless($record instanceof Prescription, 404);

        $validated = $request->validated();
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($validated['items'] ?? null) ? $validated['items'] : [];
        unset($validated['items']);

        $action->handle($record, $validated, $items);

        return back()->with('success', 'Medication dispensed and pharmacy stock updated.');
    }

    public function show(Request $request, DispensingRecord $dispensingRecord): Response
    {
        abort_unless($dispensingRecord->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless(
            $this->inventoryLocationAccess->canAccessLocationForTypes(
                Auth::user(),
                $dispensingRecord->inventory_location_id,
                ['pharmacy'],
                $dispensingRecord->branch_id,
            ),
            403,
            'You do not have access to this dispensing record.',
        );

        $dispensingRecord->load([
            'visit.patient',
            'prescription.prescribedBy',
            'inventoryLocation',
            'dispensedBy.staff',
            'items.prescriptionItem.inventoryItem',
            'items.inventoryItem',
            'items.substitutionInventoryItem',
            'items.allocations.inventoryBatch',
        ]);
        $visit = $dispensingRecord->visit;
        $patient = $visit?->patient;
        $prescription = $dispensingRecord->prescription;
        $inventoryLocation = $dispensingRecord->inventoryLocation;
        $dispensedBy = $dispensingRecord->dispensedBy;
        $staff = $dispensedBy?->staff;

        return Inertia::render('pharmacy/dispenses/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'dispensingRecord' => [
                'id' => $dispensingRecord->id,
                'dispense_number' => $dispensingRecord->dispense_number,
                'status' => $dispensingRecord->status->value,
                'status_label' => $dispensingRecord->status->label(),
                'dispensed_at' => $dispensingRecord->dispensed_at->toISOString(),
                'notes' => $dispensingRecord->notes,
                'visit_number' => $visit?->visit_number,
                'patient' => $patient === null ? null : [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => mb_trim(sprintf(
                        '%s %s',
                        $patient->first_name,
                        $patient->last_name,
                    )),
                ],
                'prescription' => $prescription === null ? null : [
                    'id' => $prescription->id,
                    'status' => $prescription->status?->value,
                    'status_label' => $prescription->status?->label(),
                    'primary_diagnosis' => $prescription->primary_diagnosis,
                    'pharmacy_notes' => $prescription->pharmacy_notes,
                ],
                'inventory_location' => $inventoryLocation === null ? null : [
                    'id' => $inventoryLocation->id,
                    'name' => $inventoryLocation->name,
                    'location_code' => $inventoryLocation->location_code,
                ],
                'dispensed_by' => $staff === null
                    ? ($dispensedBy?->email)
                    : mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name)),
                'items' => $dispensingRecord->items
                    ->map(fn (DispensingRecordItem $item): array => $this->serializeDispensingRecordItem($item))
                    ->values()
                    ->all(),
                'can_post' => $dispensingRecord->status === DispensingRecordStatus::DRAFT,
            ],
            'availableBatchBalances' => $this->availableBatchBalances($dispensingRecord),
            'pharmacyPolicy' => $this->pharmacyPolicy($dispensingRecord->tenant_id),
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
            ->map(static function (Collection $rows): float {
                return $rows->reduce(
                    static fn (float $carry, array $row): float => $carry + (float) $row['quantity'],
                    0.0,
                );
            });

        return $balances;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function availableBatchBalances(DispensingRecord $dispensingRecord): array
    {
        /** @var array<string, InventoryBatch> $batches */
        $batches = InventoryBatch::query()
            ->with('inventoryItem:id,name,generic_name')
            ->whereIn(
                'id',
                $this->inventoryStockLedger
                    ->summarizeByBatch($dispensingRecord->branch_id)
                    ->filter(
                        static fn (array $balance): bool => $balance['inventory_location_id'] === $dispensingRecord->inventory_location_id
                            && $balance['quantity'] > 0
                    )
                    ->pluck('inventory_batch_id'),
            )
            ->get()
            ->keyBy('id')
            ->all();

        return $this->inventoryStockLedger
            ->summarizeByBatch($dispensingRecord->branch_id)
            ->filter(
                static fn (array $balance): bool => $balance['inventory_location_id'] === $dispensingRecord->inventory_location_id
                    && $balance['quantity'] > 0
            )
            ->filter(function (array $balance) use ($batches): bool {
                $batch = $batches[$balance['inventory_batch_id']] ?? null;

                return ! ($batch instanceof InventoryBatch)
                    || $batch->expiry_date === null
                    || ! $batch->expiry_date->startOfDay()->isBefore(today());
            })
            ->map(static function (array $balance) use ($batches): array {
                $batch = $batches[$balance['inventory_batch_id']] ?? null;
                $inventoryItem = $batch?->inventoryItem;

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_location_id' => $balance['inventory_location_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'item_name' => $inventoryItem === null
                        ? null
                        : ($inventoryItem->generic_name ?? $inventoryItem->name),
                ];
            })
            ->sortBy(static fn (array $batch): string => sprintf(
                '%s|%s|%s',
                $batch['inventory_item_id'],
                $batch['expiry_date'] ?? '9999-12-31',
                $batch['batch_number'] ?? 'ZZZ',
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{batch_tracking_enabled: bool, enforce_fefo: bool, allow_partial_dispense: bool}
     */
    private function pharmacyPolicy(?string $tenantId): array
    {
        if (! is_string($tenantId) || $tenantId === '') {
            return [
                'batch_tracking_enabled' => true,
                'enforce_fefo' => true,
                'allow_partial_dispense' => true,
            ];
        }

        return [
            'batch_tracking_enabled' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'enable_batch_tracking_when_dispensing',
            ),
            'enforce_fefo' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'enforce_fefo',
            ),
            'allow_partial_dispense' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'allow_partial_dispense',
            ),
        ];
    }

    /**
     * @param  Collection<string, float>  $stockBalances
     * @return array<string, mixed>
     */
    private function serializeCreatePrescriptionItem(PrescriptionItem $item, Collection $stockBalances): array
    {
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
            'quantity' => round((float) $item->quantity, 3),
            'instructions' => $item->instructions,
            'status' => $item->status?->value,
            'status_label' => $item->status?->label(),
            'available_quantity' => round((float) ($stockBalances->get($item->inventory_item_id) ?? 0), 3),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDispensingRecordItem(DispensingRecordItem $item): array
    {
        $inventoryItem = $item->inventoryItem;
        $substitutionInventoryItem = $item->substitutionInventoryItem;

        return [
            'id' => $item->id,
            'prescription_item_id' => $item->prescription_item_id,
            'inventory_item_id' => $item->inventory_item_id,
            'prescribed_quantity' => round((float) $item->prescribed_quantity, 3),
            'dispensed_quantity' => round((float) $item->dispensed_quantity, 3),
            'balance_quantity' => round((float) $item->balance_quantity, 3),
            'dispense_status' => $item->dispense_status->value,
            'dispense_status_label' => $item->dispense_status->label(),
            'external_pharmacy' => $item->external_pharmacy,
            'external_reason' => $item->external_reason,
            'notes' => $item->notes,
            'item_name' => $inventoryItem?->name,
            'generic_name' => $inventoryItem?->generic_name,
            'substitution_item_name' => $substitutionInventoryItem?->name,
            'allocations' => $item->allocations
                ->map(static fn (DispensingRecordItemAllocation $allocation): array => [
                    'id' => $allocation->id,
                    'inventory_batch_id' => $allocation->inventory_batch_id,
                    'quantity' => round((float) $allocation->quantity, 3),
                    'batch_number_snapshot' => $allocation->batch_number_snapshot,
                    'expiry_date_snapshot' => $allocation->expiry_date_snapshot?->toDateString(),
                ])
                ->values()
                ->all(),
        ];
    }
}
