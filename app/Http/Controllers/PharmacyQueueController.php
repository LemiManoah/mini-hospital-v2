<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use App\Support\PrescriptionDispenseProgress;
use App\Support\PrescriptionQueueQuery;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyQueueController implements HasMiddleware
{
    public function __construct(
        private PrescriptionQueueQuery $prescriptionQueueQuery,
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryStockLedger $inventoryStockLedger,
        private PrescriptionDispenseProgress $prescriptionDispenseProgress,
        private TenantGeneralSettings $tenantGeneralSettings,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_queue.view', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));
        $branchId = BranchContext::getActiveBranchId();
        $dispensingLocations = $this->dispensingLocations();
        $stockBalances = is_string($branchId) && $branchId !== ''
            ? $this->itemBalancesForLocations($branchId, $dispensingLocations)
            : collect();

        /** @var LengthAwarePaginator<int, Prescription> $prescriptions */
        $prescriptions = $this->prescriptionQueueQuery
            ->paginate($search, $status)
            ->through(fn (Prescription $prescription): array => $this->serializePrescriptionSummary($prescription, $stockBalances));

        return Inertia::render('pharmacy/queue', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'prescriptions' => $prescriptions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => [
                [
                    'value' => PrescriptionStatus::PENDING->value,
                    'label' => PrescriptionStatus::PENDING->label(),
                ],
                [
                    'value' => PrescriptionStatus::PARTIALLY_DISPENSED->value,
                    'label' => PrescriptionStatus::PARTIALLY_DISPENSED->label(),
                ],
            ],
            'dispensingLocations' => $dispensingLocations
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                    'is_dispensing_point' => $location->is_dispensing_point,
                ])
                ->values()
                ->all(),
            'availableBatchBalances' => is_string($branchId) && $branchId !== ''
                ? $this->availableBatchBalances($branchId, $dispensingLocations)
                : [],
            'pharmacyPolicy' => $this->pharmacyPolicy($dispensingLocations->first()?->tenant_id),
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
            /** @var Collection<string, float> $emptyBalances */
            $emptyBalances = collect();

            return $emptyBalances;
        }

        /** @var array<string, float> $balances */
        $balances = $this->inventoryStockLedger
            ->summarizeByLocation($branchId)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
            ->reduce(
                static function (array $carry, array $balance): array {
                    $itemId = $balance['inventory_item_id'];
                    $currentQuantity = 0.0;

                    if (array_key_exists($itemId, $carry)) {
                        $existingQuantity = $carry[$itemId];

                        if (is_float($existingQuantity) || is_int($existingQuantity)) {
                            $currentQuantity = (float) $existingQuantity;
                        }
                    }

                    $carry[$itemId] = $currentQuantity + $balance['quantity'];

                    return $carry;
                },
                [],
            );

        /** @var Collection<string, float> $balanceCollection */
        $balanceCollection = collect($balances);

        return $balanceCollection;
    }

    /**
     * @param  Collection<string, float>  $stockBalances
     * @return array<string, mixed>
     */
    private function serializePrescriptionSummary(Prescription $prescription, Collection $stockBalances): array
    {
        $progress = $this->prescriptionDispenseProgress->postedLineSummaries($prescription->id);

        /** @var Collection<int, array{
         *   id: string,
         *   inventory_item_id: string,
         *   item_name: string|null,
         *   generic_name: string|null,
         *   brand_name: string|null,
         *   strength: string|null,
         *   dosage_form: string|null,
         *   dosage: string,
         *   frequency: string,
         *   route: string,
         *   duration_days: int,
         *   quantity: float,
         *   remaining_quantity: float,
         *   covered_quantity: float,
         *   locally_dispensed_quantity: float,
         *   instructions: string|null,
         *   status: string|null,
         *   status_label: string|null,
         *   dispensed_at: string|null,
         *   external_pharmacy: bool,
         *   available_quantity: float,
         *   stock_status: string,
         *   stock_status_label: string
         * }> $items
         */
        $items = $prescription->items
            ->map(fn (PrescriptionItem $item): array => $this->serializeItem($item, $stockBalances, $progress->get($item->id)))
            ->filter(static fn (array $item): bool => $item['remaining_quantity'] > 0.0005)
            ->values();

        $availability = $this->resolveAvailabilitySummary(array_values($items->all()));

        return [
            'id' => $prescription->id,
            'visit_id' => $prescription->visit_id,
            'visit_number' => $prescription->visit?->visit_number,
            'prescription_date' => $prescription->prescription_date?->toISOString(),
            'status' => $prescription->status?->value,
            'status_label' => $prescription->status?->label(),
            'primary_diagnosis' => $prescription->primary_diagnosis,
            'pharmacy_notes' => $prescription->pharmacy_notes,
            'patient' => $prescription->visit?->patient === null ? null : [
                'id' => $prescription->visit->patient->id,
                'patient_number' => $prescription->visit->patient->patient_number,
                'full_name' => mb_trim(sprintf(
                    '%s %s',
                    $prescription->visit->patient->first_name,
                    $prescription->visit->patient->last_name,
                )),
                'gender' => $prescription->visit->patient->gender,
                'phone_number' => $prescription->visit->patient->phone_number,
            ],
            'prescribed_by' => $prescription->prescribedBy === null ? null : [
                'id' => $prescription->prescribedBy->id,
                'name' => mb_trim(sprintf(
                    '%s %s',
                    $prescription->prescribedBy->first_name,
                    $prescription->prescribedBy->last_name,
                )),
            ],
            'items' => $items->all(),
            'availability' => $availability,
            'items_count' => $items->count(),
            'pending_items_count' => $items->where('status', PrescriptionItemStatus::PENDING->value)->count(),
        ];
    }

    /**
     * @param  Collection<string, float>  $stockBalances
     * @return array<string, mixed>
     */
    /**
     * @param  Collection<string, float>  $stockBalances
     * @param  array{
     *   dispensed_quantity: float,
     *   external_quantity: float,
     *   covered_quantity: float,
     *   latest_dispensed_at: Carbon|null,
     *   external_pharmacy: bool
     * }|null  $progress
     * @return array{
     *   id: string,
     *   inventory_item_id: string,
     *   item_name: string|null,
     *   generic_name: string|null,
     *   brand_name: string|null,
     *   strength: string|null,
     *   dosage_form: string|null,
     *   dosage: string,
     *   frequency: string,
     *   route: string,
     *   duration_days: int,
     *   quantity: float,
     *   remaining_quantity: float,
     *   covered_quantity: float,
     *   locally_dispensed_quantity: float,
     *   instructions: string|null,
     *   status: string|null,
     *   status_label: string|null,
     *   dispensed_at: string|null,
     *   external_pharmacy: bool,
     *   available_quantity: float,
     *   stock_status: string,
     *   stock_status_label: string
     * }
     */
    private function serializeItem(PrescriptionItem $item, Collection $stockBalances, ?array $progress): array
    {
        $requestedQuantity = round((float) $item->quantity, 3);
        $coveredQuantity = min($requestedQuantity, round((float) ($progress['covered_quantity'] ?? 0), 3));
        $locallyDispensedQuantity = min($requestedQuantity, round((float) ($progress['dispensed_quantity'] ?? 0), 3));
        $remainingQuantity = max(0, round($requestedQuantity - $coveredQuantity, 3));
        $availableQuantity = round((float) ($stockBalances->get((string) $item->inventory_item_id) ?? 0), 3);
        $stockStatus = match (true) {
            $remainingQuantity <= 0 => 'ready',
            $availableQuantity >= $remainingQuantity && $remainingQuantity > 0 => 'ready',
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
            'dosage_form' => $item->inventoryItem?->dosage_form?->value,
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
            'external_pharmacy' => (bool) ($item->is_external_pharmacy ?? false),
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
     * @param  list<array{stock_status: string}>  $items
     * @return array<string, mixed>
     */
    private function resolveAvailabilitySummary(array $items): array
    {
        if ($items === []) {
            return [
                'status' => 'out_of_stock',
                'label' => 'No Pending Lines',
                'ready_items' => 0,
                'partial_items' => 0,
                'out_of_stock_items' => 0,
            ];
        }

        $itemCollection = collect($items);
        $readyCount = $itemCollection->where('stock_status', 'ready')->count();
        $partialCount = $itemCollection->where('stock_status', 'partial')->count();
        $outOfStockCount = $itemCollection->where('stock_status', 'out_of_stock')->count();

        $status = match (true) {
            $outOfStockCount === 0 && $partialCount === 0 => 'ready',
            $readyCount > 0 || $partialCount > 0 => 'partial',
            default => 'out_of_stock',
        };

        return [
            'status' => $status,
            'label' => match ($status) {
                'ready' => 'Ready',
                'partial' => 'Partial Stock',
                default => 'Out Of Stock',
            },
            'ready_items' => $readyCount,
            'partial_items' => $partialCount,
            'out_of_stock_items' => $outOfStockCount,
        ];
    }

    /**
     * @param  Collection<int, InventoryLocation>  $locations
     * @return array<int, array<string, mixed>>
     */
    private function availableBatchBalances(string $branchId, Collection $locations): array
    {
        $locationIds = $locations
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        /** @var array<string, InventoryBatch> $batches */
        $batches = InventoryBatch::query()
            ->with('inventoryItem:id,name,generic_name')
            ->whereIn(
                'id',
                $this->inventoryStockLedger
                    ->summarizeByBatch($branchId)
                    ->filter(
                        static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true)
                            && $balance['quantity'] > 0
                    )
                    ->pluck('inventory_batch_id'),
            )
            ->get()
            ->keyBy('id')
            ->all();

        return $this->inventoryStockLedger
            ->summarizeByBatch($branchId)
            ->filter(
                static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true)
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
                    'item_name' => $inventoryItem === null ? null : ($inventoryItem->generic_name ?? $inventoryItem->name),
                ];
            })
            ->sortBy(static fn (array $batch): string => sprintf(
                '%s|%s|%s|%s',
                $batch['inventory_location_id'],
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
}
