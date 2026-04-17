<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DispensePharmacyTreatmentPlanCycle;
use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Http\Requests\DispensePharmacyTreatmentPlanCycleRequest;
use App\Models\InventoryBatch;
use App\Models\InventoryLocation;
use App\Models\PharmacyTreatmentPlan;
use App\Models\PharmacyTreatmentPlanCycle;
use App\Support\BranchContext;
use App\Support\GeneralSettings\TenantGeneralSettings;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyTreatmentPlanCycleController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryStockLedger $inventoryStockLedger,
        private TenantGeneralSettings $tenantGeneralSettings,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['create', 'store']),
        ];
    }

    public function create(Request $request, PharmacyTreatmentPlan $treatmentPlan, PharmacyTreatmentPlanCycle $cycle): Response
    {
        abort_unless($treatmentPlan->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cycle->pharmacy_treatment_plan_id === $treatmentPlan->id, 404);
        abort_unless($treatmentPlan->status === PharmacyTreatmentPlanStatus::ACTIVE, 404);
        abort_unless($cycle->status === PharmacyTreatmentPlanCycleStatus::PENDING, 404);

        $treatmentPlan->load([
            'visit.patient',
            'items.inventoryItem',
            'items.prescriptionItem',
        ]);

        $locations = $this->dispensingLocations();

        return Inertia::render('pharmacy/treatment-plans/dispense-cycle', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'treatmentPlan' => [
                'id' => $treatmentPlan->id,
                'visit_number' => $treatmentPlan->visit?->visit_number,
                'patient' => $treatmentPlan->visit?->patient === null ? null : [
                    'id' => $treatmentPlan->visit->patient->id,
                    'patient_number' => $treatmentPlan->visit->patient->patient_number,
                    'full_name' => mb_trim(sprintf('%s %s', $treatmentPlan->visit->patient->first_name, $treatmentPlan->visit->patient->last_name)),
                ],
                'cycle' => [
                    'id' => $cycle->id,
                    'cycle_number' => $cycle->cycle_number,
                    'scheduled_for' => $cycle->scheduled_for?->toDateString(),
                ],
                'items' => $treatmentPlan->items->map(static fn ($item): array => [
                    'id' => $item->id,
                    'prescription_item_id' => $item->prescription_item_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $item->inventoryItem?->name,
                    'generic_name' => $item->inventoryItem?->generic_name,
                    'dosage' => $item->prescriptionItem?->dosage,
                    'frequency' => $item->prescriptionItem?->frequency,
                    'route' => $item->prescriptionItem?->route,
                    'instructions' => $item->prescriptionItem?->instructions,
                    'quantity_per_cycle' => round((float) $item->quantity_per_cycle, 3),
                ])->values()->all(),
            ],
            'dispensingLocations' => $locations
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                    'is_dispensing_point' => $location->is_dispensing_point,
                ])->values()->all(),
            'availableBatchBalances' => is_string(BranchContext::getActiveBranchId()) && BranchContext::getActiveBranchId() !== ''
                ? $this->availableBatchBalances((string) BranchContext::getActiveBranchId(), $locations)
                : [],
            'pharmacyPolicy' => $this->pharmacyPolicy($treatmentPlan->tenant_id),
            'defaults' => [
                'inventory_location_id' => $locations->first()?->id,
                'dispensed_at' => now()->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function store(
        DispensePharmacyTreatmentPlanCycleRequest $request,
        PharmacyTreatmentPlan $treatmentPlan,
        PharmacyTreatmentPlanCycle $cycle,
        DispensePharmacyTreatmentPlanCycle $action,
    ): RedirectResponse {
        abort_unless($treatmentPlan->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cycle->pharmacy_treatment_plan_id === $treatmentPlan->id, 404);

        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $record = $action->handle($treatmentPlan, $cycle, $validated, $items);

        return to_route('pharmacy.treatment-plans.show', $treatmentPlan)
            ->with('success', sprintf('Treatment cycle dispensed successfully under %s.', $record->dispense_number));
    }

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

    private function availableBatchBalances(string $branchId, Collection $locations): array
    {
        $locationIds = $locations->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        $batches = InventoryBatch::query()
            ->with('inventoryItem:id,name,generic_name')
            ->whereIn(
                'id',
                $this->inventoryStockLedger
                    ->summarizeByBatch($branchId)
                    ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true) && $balance['quantity'] > 0)
                    ->pluck('inventory_batch_id'),
            )
            ->get()
            ->keyBy('id')
            ->all();

        return $this->inventoryStockLedger
            ->summarizeByBatch($branchId)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true) && $balance['quantity'] > 0)
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
                    'inventory_location_id' => $balance['inventory_location_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => (float) $balance['quantity'],
                    'item_name' => $batch?->inventoryItem?->generic_name ?? $batch?->inventoryItem?->name,
                ];
            })
            ->values()
            ->all();
    }

    private function pharmacyPolicy(?string $tenantId): array
    {
        if (! is_string($tenantId) || $tenantId === '') {
            return [
                'batch_tracking_enabled' => true,
                'allow_partial_dispense' => true,
            ];
        }

        return [
            'batch_tracking_enabled' => $this->tenantGeneralSettings->boolean($tenantId, 'enable_batch_tracking_when_dispensing'),
            'allow_partial_dispense' => $this->tenantGeneralSettings->boolean($tenantId, 'allow_partial_dispense'),
        ];
    }
}
