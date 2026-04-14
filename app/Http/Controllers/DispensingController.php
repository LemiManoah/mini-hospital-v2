<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDispensingRecord;
use App\Actions\PostDispense;
use App\Enums\DispensingRecordStatus;
use App\Http\Requests\PostDispenseRequest;
use App\Http\Requests\StoreDispenseRequest;
use App\Models\DispensingRecord;
use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\Prescription;
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
            new Middleware('permission:visits.view', only: ['create', 'store', 'show', 'post']),
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

        return Inertia::render('pharmacy/dispenses/create', [
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
                'items' => $record->items
                    ->map(function ($item) use ($stockBalances): array {
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
                            'quantity' => round((float) $item->quantity, 3),
                            'instructions' => $item->instructions,
                            'status' => $item->status?->value,
                            'status_label' => $item->status?->label(),
                            'available_quantity' => round((float) ($stockBalances->get((string) $item->inventory_item_id) ?? 0), 3),
                        ];
                    })
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
        $items = $validated['items'];
        unset($validated['items']);

        $dispensingRecord = $action->handle($record, $validated, $items);

        return to_route('pharmacy.dispenses.show', ['dispensingRecord' => $dispensingRecord])
            ->with('success', 'Dispensing record created. Review and post it when you are ready to update stock.');
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

        $postedRecord = $action->handle(
            $dispensingRecord,
            is_array($validated['items'] ?? null) ? $validated['items'] : [],
        );

        return to_route('pharmacy.dispenses.show', ['dispensingRecord' => $postedRecord])
            ->with('success', 'Dispense posted successfully and pharmacy stock has been updated.');
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

        return Inertia::render('pharmacy/dispenses/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'dispensingRecord' => [
                'id' => $dispensingRecord->id,
                'dispense_number' => $dispensingRecord->dispense_number,
                'status' => $dispensingRecord->status?->value,
                'status_label' => $dispensingRecord->status?->label(),
                'dispensed_at' => $dispensingRecord->dispensed_at?->toISOString(),
                'notes' => $dispensingRecord->notes,
                'visit_number' => $dispensingRecord->visit?->visit_number,
                'patient' => $dispensingRecord->visit?->patient === null ? null : [
                    'id' => $dispensingRecord->visit->patient->id,
                    'patient_number' => $dispensingRecord->visit->patient->patient_number,
                    'full_name' => mb_trim(sprintf(
                        '%s %s',
                        $dispensingRecord->visit->patient->first_name,
                        $dispensingRecord->visit->patient->last_name,
                    )),
                ],
                'prescription' => $dispensingRecord->prescription === null ? null : [
                    'id' => $dispensingRecord->prescription->id,
                    'status' => $dispensingRecord->prescription->status?->value,
                    'status_label' => $dispensingRecord->prescription->status?->label(),
                    'primary_diagnosis' => $dispensingRecord->prescription->primary_diagnosis,
                    'pharmacy_notes' => $dispensingRecord->prescription->pharmacy_notes,
                ],
                'inventory_location' => $dispensingRecord->inventoryLocation === null ? null : [
                    'id' => $dispensingRecord->inventoryLocation->id,
                    'name' => $dispensingRecord->inventoryLocation->name,
                    'location_code' => $dispensingRecord->inventoryLocation->location_code,
                ],
                'dispensed_by' => $dispensingRecord->dispensedBy?->staff === null
                    ? ($dispensingRecord->dispensedBy?->email)
                    : mb_trim(sprintf(
                        '%s %s',
                        $dispensingRecord->dispensedBy->staff->first_name,
                        $dispensingRecord->dispensedBy->staff->last_name,
                    )),
                'items' => $dispensingRecord->items
                    ->map(static fn ($item): array => [
                        'id' => $item->id,
                        'prescription_item_id' => $item->prescription_item_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'prescribed_quantity' => round((float) $item->prescribed_quantity, 3),
                        'dispensed_quantity' => round((float) $item->dispensed_quantity, 3),
                        'balance_quantity' => round((float) $item->balance_quantity, 3),
                        'dispense_status' => $item->dispense_status?->value,
                        'dispense_status_label' => $item->dispense_status?->label(),
                        'external_pharmacy' => $item->external_pharmacy,
                        'external_reason' => $item->external_reason,
                        'notes' => $item->notes,
                        'item_name' => $item->inventoryItem?->name,
                        'generic_name' => $item->inventoryItem?->generic_name,
                        'substitution_item_name' => $item->substitutionInventoryItem?->name,
                        'allocations' => $item->allocations
                            ->map(static fn ($allocation): array => [
                                'id' => $allocation->id,
                                'inventory_batch_id' => $allocation->inventory_batch_id,
                                'quantity' => round((float) $allocation->quantity, 3),
                                'batch_number_snapshot' => $allocation->batch_number_snapshot,
                                'expiry_date_snapshot' => $allocation->expiry_date_snapshot?->toDateString(),
                            ])
                            ->values()
                            ->all(),
                    ])
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
            return collect();
        }

        return $this->inventoryStockLedger
            ->summarizeByLocation($branchId)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $rows): float => (float) $rows->sum('quantity'));
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
                    || ! $batch->expiry_date->startOfDay()->isBefore(now()->startOfDay());
            })
            ->map(static function (array $balance) use ($batches): array {
                $batch = $batches[$balance['inventory_batch_id']] ?? null;

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => (float) $balance['quantity'],
                    'item_name' => $batch?->inventoryItem?->generic_name ?? $batch?->inventoryItem?->name,
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
     * @return array{batch_tracking_enabled: bool, allow_partial_dispense: bool}
     */
    private function pharmacyPolicy(?string $tenantId): array
    {
        if (! is_string($tenantId) || $tenantId === '') {
            return [
                'batch_tracking_enabled' => true,
                'allow_partial_dispense' => true,
            ];
        }

        return [
            'batch_tracking_enabled' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'enable_batch_tracking_when_dispensing',
            ),
            'allow_partial_dispense' => $this->tenantGeneralSettings->boolean(
                $tenantId,
                'allow_partial_dispense',
            ),
        ];
    }
}
