<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePharmacyTreatmentPlan;
use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Http\Requests\StorePharmacyTreatmentPlanRequest;
use App\Models\PharmacyTreatmentPlan;
use App\Models\Prescription;
use App\Support\BranchContext;
use App\Support\InventoryNavigationContext;
use App\Support\PrescriptionDispenseProgress;
use App\Support\PrescriptionQueueQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyTreatmentPlanController implements HasMiddleware
{
    public function __construct(
        private PrescriptionQueueQuery $prescriptionQueueQuery,
        private PrescriptionDispenseProgress $prescriptionDispenseProgress,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:visits.view', only: ['index', 'create', 'store', 'show']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));
        $due = mb_trim((string) $request->query('due', ''));
        $today = now()->toDateString();

        $query = PharmacyTreatmentPlan::query()
            ->with([
                'visit.patient:id,patient_number,first_name,last_name',
                'prescription:id,visit_id,prescribed_by',
                'prescription.prescribedBy:id,first_name,last_name',
                'items:id,pharmacy_treatment_plan_id,inventory_item_id,quantity_per_cycle',
                'items.inventoryItem:id,name,generic_name',
                'cycles:id,pharmacy_treatment_plan_id,cycle_number,scheduled_for,status,completed_at,dispensing_record_id',
            ])
            ->when(is_string(BranchContext::getActiveBranchId()) && BranchContext::getActiveBranchId() !== '', static function (Builder $builder): void {
                $builder->where('branch_id', BranchContext::getActiveBranchId());
            })
            ->when($status !== '' && in_array($status, array_column(PharmacyTreatmentPlanStatus::cases(), 'value'), true), static fn (Builder $builder) => $builder->where('status', $status))
            ->when($due !== '', function (Builder $builder) use ($due, $today): void {
                if ($due === 'overdue') {
                    $builder->where('status', PharmacyTreatmentPlanStatus::ACTIVE)->whereDate('next_refill_date', '<', $today);
                } elseif ($due === 'today') {
                    $builder->where('status', PharmacyTreatmentPlanStatus::ACTIVE)->whereDate('next_refill_date', '=', $today);
                } elseif ($due === 'upcoming') {
                    $builder->where('status', PharmacyTreatmentPlanStatus::ACTIVE)->whereDate('next_refill_date', '>', $today);
                }
            })
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $inner) use ($search): void {
                    $inner->whereHas('visit', static fn (Builder $visitQuery) => $visitQuery->where('visit_number', 'like', "%{$search}%"))
                        ->orWhereHas('visit.patient', static function (Builder $patientQuery) use ($search): void {
                            $patientQuery->where('patient_number', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.inventoryItem', static function (Builder $itemQuery) use ($search): void {
                            $itemQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('generic_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [PharmacyTreatmentPlanStatus::ACTIVE->value])
            ->orderBy('next_refill_date')
            ->orderByDesc('created_at');

        $plans = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PharmacyTreatmentPlan $plan): array => $this->serializePlanSummary($plan));

        return Inertia::render('pharmacy/treatment-plans/index', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'plans' => $plans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'due' => $due,
            ],
            'statusOptions' => array_map(static fn (PharmacyTreatmentPlanStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], PharmacyTreatmentPlanStatus::cases()),
            'dueOptions' => [
                ['value' => 'overdue', 'label' => 'Overdue'],
                ['value' => 'today', 'label' => 'Due Today'],
                ['value' => 'upcoming', 'label' => 'Upcoming'],
            ],
        ]);
    }

    public function create(Request $request, Prescription $prescription): Response
    {
        $record = $this->prescriptionQueueQuery->findForPharmacy($prescription->id);

        abort_unless($record instanceof Prescription, 404);

        $activePlan = $record->pharmacyTreatmentPlans()
            ->where('status', PharmacyTreatmentPlanStatus::ACTIVE)
            ->first();

        abort_if($activePlan instanceof PharmacyTreatmentPlan, 409, 'This prescription already has an active treatment plan.');

        $progress = $this->prescriptionDispenseProgress->postedLineSummaries($record->id);

        return Inertia::render('pharmacy/treatment-plans/create', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'prescription' => [
                'id' => $record->id,
                'visit_number' => $record->visit?->visit_number,
                'prescription_date' => $record->prescription_date?->toISOString(),
                'patient' => $record->visit?->patient === null ? null : [
                    'id' => $record->visit->patient->id,
                    'patient_number' => $record->visit->patient->patient_number,
                    'full_name' => mb_trim(sprintf('%s %s', $record->visit->patient->first_name, $record->visit->patient->last_name)),
                ],
                'items' => $record->items->map(function ($item) use ($progress): array {
                    $orderedQuantity = round((float) $item->quantity, 3);
                    $remainingQuantity = max(
                        0,
                        round($orderedQuantity - (float) ($progress->get($item->id)['covered_quantity'] ?? 0.0), 3),
                    );

                    return [
                        'id' => $item->id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'item_name' => $item->inventoryItem?->name,
                        'generic_name' => $item->inventoryItem?->generic_name,
                        'dosage' => $item->dosage,
                        'frequency' => $item->frequency,
                        'route' => $item->route,
                        'ordered_quantity' => $orderedQuantity,
                        'remaining_quantity' => $remainingQuantity,
                    ];
                })->filter(static fn (array $item): bool => $item['remaining_quantity'] > 0.0005)->values()->all(),
            ],
        ]);
    }

    public function store(
        StorePharmacyTreatmentPlanRequest $request,
        Prescription $prescription,
        CreatePharmacyTreatmentPlan $action,
    ): RedirectResponse {
        $record = $this->prescriptionQueueQuery->findForPharmacy($prescription->id);

        abort_unless($record instanceof Prescription, 404);

        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $plan = $action->handle($record, $validated, $items);

        return to_route('pharmacy.treatment-plans.show', $plan)
            ->with('success', 'Treatment plan created successfully.');
    }

    public function show(Request $request, PharmacyTreatmentPlan $treatmentPlan): Response
    {
        abort_unless($treatmentPlan->branch_id === BranchContext::getActiveBranchId(), 404);

        $treatmentPlan->load([
            'visit.patient',
            'prescription.prescribedBy',
            'items.inventoryItem',
            'items.prescriptionItem',
            'cycles.dispensingRecord',
        ]);

        $nextPendingCycle = $treatmentPlan->cycles
            ->where('status', PharmacyTreatmentPlanCycleStatus::PENDING)
            ->sortBy('cycle_number')
            ->first();

        return Inertia::render('pharmacy/treatment-plans/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'treatmentPlan' => [
                'id' => $treatmentPlan->id,
                'status' => $treatmentPlan->status?->value,
                'status_label' => $treatmentPlan->status?->label(),
                'visit_number' => $treatmentPlan->visit?->visit_number,
                'patient' => $treatmentPlan->visit?->patient === null ? null : [
                    'id' => $treatmentPlan->visit->patient->id,
                    'patient_number' => $treatmentPlan->visit->patient->patient_number,
                    'full_name' => mb_trim(sprintf('%s %s', $treatmentPlan->visit->patient->first_name, $treatmentPlan->visit->patient->last_name)),
                ],
                'prescribed_by' => $treatmentPlan->prescription?->prescribedBy === null ? null : [
                    'id' => $treatmentPlan->prescription->prescribedBy->id,
                    'name' => mb_trim(sprintf('%s %s', $treatmentPlan->prescription->prescribedBy->first_name, $treatmentPlan->prescription->prescribedBy->last_name)),
                ],
                'start_date' => $treatmentPlan->start_date?->toDateString(),
                'frequency_unit' => $treatmentPlan->frequency_unit?->value,
                'frequency_unit_label' => $treatmentPlan->frequency_unit?->label(),
                'frequency_interval' => $treatmentPlan->frequency_interval,
                'total_authorized_cycles' => $treatmentPlan->total_authorized_cycles,
                'completed_cycles' => $treatmentPlan->completed_cycles,
                'next_refill_date' => $treatmentPlan->next_refill_date?->toDateString(),
                'notes' => $treatmentPlan->notes,
                'items' => $treatmentPlan->items->map(static fn ($item): array => [
                    'id' => $item->id,
                    'item_name' => $item->inventoryItem?->name,
                    'generic_name' => $item->inventoryItem?->generic_name,
                    'quantity_per_cycle' => round((float) $item->quantity_per_cycle, 3),
                    'authorized_total_quantity' => round((float) $item->authorized_total_quantity, 3),
                    'total_cycles' => $item->total_cycles,
                    'completed_cycles' => $item->completed_cycles,
                    'remaining_cycles' => max(0, $item->total_cycles - $item->completed_cycles),
                ])->values()->all(),
                'cycles' => $treatmentPlan->cycles->sortBy('cycle_number')->values()->map(fn ($cycle): array => [
                    'id' => $cycle->id,
                    'cycle_number' => $cycle->cycle_number,
                    'scheduled_for' => $cycle->scheduled_for?->toDateString(),
                    'status' => $cycle->status?->value,
                    'status_label' => $cycle->status?->label(),
                    'completed_at' => $cycle->completed_at?->toISOString(),
                    'state' => $this->cycleState($cycle->status, $cycle->scheduled_for?->toDateString()),
                    'dispensing_record' => $cycle->dispensingRecord === null ? null : [
                        'id' => $cycle->dispensingRecord->id,
                        'dispense_number' => $cycle->dispensingRecord->dispense_number,
                    ],
                ])->all(),
                'next_pending_cycle' => $nextPendingCycle === null ? null : [
                    'id' => $nextPendingCycle->id,
                    'cycle_number' => $nextPendingCycle->cycle_number,
                    'scheduled_for' => $nextPendingCycle->scheduled_for?->toDateString(),
                    'state' => $this->cycleState($nextPendingCycle->status, $nextPendingCycle->scheduled_for?->toDateString()),
                ],
            ],
        ]);
    }

    private function serializePlanSummary(PharmacyTreatmentPlan $plan): array
    {
        $patient = $plan->visit?->patient;

        return [
            'id' => $plan->id,
            'status' => $plan->status?->value,
            'status_label' => $plan->status?->label(),
            'visit_number' => $plan->visit?->visit_number,
            'patient_name' => $patient === null ? null : mb_trim(sprintf('%s %s', $patient->first_name, $patient->last_name)),
            'patient_number' => $patient?->patient_number,
            'frequency_unit_label' => $plan->frequency_unit?->label(),
            'frequency_interval' => $plan->frequency_interval,
            'total_authorized_cycles' => $plan->total_authorized_cycles,
            'completed_cycles' => $plan->completed_cycles,
            'remaining_cycles' => max(0, $plan->total_authorized_cycles - $plan->completed_cycles),
            'next_refill_date' => $plan->next_refill_date?->toDateString(),
            'due_state' => $this->cycleState($plan->status === PharmacyTreatmentPlanStatus::ACTIVE ? PharmacyTreatmentPlanCycleStatus::PENDING : PharmacyTreatmentPlanCycleStatus::COMPLETED, $plan->next_refill_date?->toDateString()),
            'item_names' => $plan->items
                ->map(static fn ($item): string => $item->inventoryItem?->generic_name ?? $item->inventoryItem?->name ?? 'Medication')
                ->take(3)
                ->values()
                ->all(),
        ];
    }

    private function cycleState(PharmacyTreatmentPlanCycleStatus|string|null $status, ?string $scheduledFor): string
    {
        if ($status === PharmacyTreatmentPlanCycleStatus::COMPLETED || $status === PharmacyTreatmentPlanCycleStatus::COMPLETED->value) {
            return 'completed';
        }

        if ($status === PharmacyTreatmentPlanCycleStatus::CANCELLED || $status === PharmacyTreatmentPlanCycleStatus::CANCELLED->value) {
            return 'cancelled';
        }

        if (! is_string($scheduledFor) || $scheduledFor === '') {
            return 'pending';
        }

        $today = now()->toDateString();

        if ($scheduledFor < $today) {
            return 'overdue';
        }

        if ($scheduledFor === $today) {
            return 'due_today';
        }

        return 'upcoming';
    }
}
