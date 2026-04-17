<?php

declare(strict_types=1);

use App\Enums\DispensingRecordStatus;
use App\Enums\PharmacyTreatmentPlanCycleStatus;
use App\Enums\PharmacyTreatmentPlanStatus;
use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\StockMovementType;
use App\Models\DispensingRecord;
use App\Models\PharmacyTreatmentPlan;
use App\Models\StockMovement;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

require_once __DIR__.'/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('shows the treatment plan creation page with the remaining prescription quantities', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        ,
        ,
        ,
        $readyDrug,
        ,
        ,
    ] = createPharmacyModuleContext();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 12,
            ],
        ],
        PrescriptionStatus::PENDING,
        'treatment-plan-create',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.treatment-plans.create', $prescription))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/treatment-plans/create')
            ->where('prescription.id', $prescription->id)
            ->where('prescription.patient.patient_number', 'PAT-PH-treatment-plan-create')
            ->where('prescription.items.0.remaining_quantity', 12.0));
});

it('creates a staged treatment plan with scheduled refill cycles', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        ,
        ,
        ,
        $readyDrug,
        ,
        ,
    ] = createPharmacyModuleContext();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 12,
            ],
        ],
        PrescriptionStatus::PENDING,
        'treatment-plan-store',
    );

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.treatment-plans.store', $prescription), [
            'start_date' => now()->toDateString(),
            'frequency_unit' => 'weekly',
            'frequency_interval' => 2,
            'total_authorized_cycles' => 3,
            'notes' => 'Dispense this chronic medication in scheduled refills.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'quantity_per_cycle' => 4,
                    'notes' => 'Review adherence at each cycle.',
                ],
            ],
        ]);

    $plan = PharmacyTreatmentPlan::query()
        ->with(['items', 'cycles'])
        ->latest('created_at')
        ->first();

    expect($plan)->not->toBeNull()
        ->and($plan?->prescription_id)->toBe($prescription->id)
        ->and($plan?->status)->toBe(PharmacyTreatmentPlanStatus::ACTIVE)
        ->and($plan?->frequency_unit?->value)->toBe('weekly')
        ->and($plan?->frequency_interval)->toBe(2)
        ->and($plan?->total_authorized_cycles)->toBe(3)
        ->and($plan?->completed_cycles)->toBe(0)
        ->and($plan?->next_refill_date?->toDateString())->toBe(now()->toDateString())
        ->and($plan?->items)->toHaveCount(1)
        ->and((float) $plan?->items[0]->quantity_per_cycle)->toBe(4.0)
        ->and((float) $plan?->items[0]->authorized_total_quantity)->toBe(12.0)
        ->and($plan?->cycles)->toHaveCount(3)
        ->and($plan?->cycles->pluck('cycle_number')->all())->toBe([1, 2, 3])
        ->and($plan?->cycles->pluck('scheduled_for')->map->toDateString()->all())->toBe([
            now()->toDateString(),
            now()->addWeeks(2)->toDateString(),
            now()->addWeeks(4)->toDateString(),
        ]);

    $response->assertRedirect(route('pharmacy.treatment-plans.show', $plan));
});

it('shows an active treatment plan in the pharmacy queue and on the prescription detail page', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        ,
        ,
        ,
        $readyDrug,
        ,
        ,
    ] = createPharmacyModuleContext();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 12,
            ],
        ],
        PrescriptionStatus::PENDING,
        'treatment-plan-queue',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.treatment-plans.store', $prescription), [
            'start_date' => now()->toDateString(),
            'frequency_unit' => 'monthly',
            'frequency_interval' => 1,
            'total_authorized_cycles' => 3,
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'quantity_per_cycle' => 4,
                    'notes' => '',
                ],
            ],
        ])
        ->assertRedirect();

    $plan = PharmacyTreatmentPlan::query()->latest('created_at')->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.queue.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('prescriptions.data.0.id', $prescription->id)
            ->where('prescriptions.data.0.active_treatment_plan.id', $plan->id)
            ->where('prescriptions.data.0.active_treatment_plan.total_authorized_cycles', 3));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.prescriptions.show', $prescription))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('prescription.id', $prescription->id)
            ->has('prescription.treatment_plans', 1)
            ->where('prescription.treatment_plans.0.id', $plan->id)
            ->where('prescription.treatment_plans.0.status', PharmacyTreatmentPlanStatus::ACTIVE->value));
});

it('dispenses a treatment cycle through the existing pharmacy stock workflow', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        $pharmacyLocation,
        ,
        ,
        $readyDrug,
        ,
        ,
        $readyDrugBatch,
    ] = createPharmacyModuleContext();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 12,
            ],
        ],
        PrescriptionStatus::PENDING,
        'treatment-plan-dispense',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.treatment-plans.store', $prescription), [
            'start_date' => now()->toDateString(),
            'frequency_unit' => 'weekly',
            'frequency_interval' => 1,
            'total_authorized_cycles' => 3,
            'notes' => 'Weekly refill program.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'quantity_per_cycle' => 4,
                    'notes' => '',
                ],
            ],
        ])
        ->assertRedirect();

    $plan = PharmacyTreatmentPlan::query()
        ->with(['items', 'cycles'])
        ->latest('created_at')
        ->firstOrFail();
    $cycle = $plan->cycles->firstOrFail();
    $planItem = $plan->items->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.treatment-plans.cycles.create', [
            'treatmentPlan' => $plan,
            'cycle' => $cycle,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/treatment-plans/dispense-cycle')
            ->where('treatmentPlan.id', $plan->id)
            ->where('treatmentPlan.cycle.id', $cycle->id)
            ->where('dispensingLocations.0.id', $pharmacyLocation->id)
            ->where('availableBatchBalances.0.inventory_batch_id', $readyDrugBatch->id));

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.treatment-plans.cycles.store', [
            'treatmentPlan' => $plan,
            'cycle' => $cycle,
        ]), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Cycle one completed.',
            'items' => [
                [
                    'pharmacy_treatment_plan_item_id' => $planItem->id,
                    'dispensed_quantity' => 4,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => '',
                    'allocations' => [
                        [
                            'inventory_batch_id' => $readyDrugBatch->id,
                            'quantity' => 4,
                        ],
                    ],
                ],
            ],
        ]);

    $record = DispensingRecord::query()->latest('created_at')->firstOrFail();
    $plan->refresh()->load(['items', 'cycles']);
    $cycle->refresh();
    $prescription->refresh()->load('items');

    expect($record->status)->toBe(DispensingRecordStatus::POSTED)
        ->and($cycle->status)->toBe(PharmacyTreatmentPlanCycleStatus::COMPLETED)
        ->and($cycle->dispensing_record_id)->toBe($record->id)
        ->and($plan->completed_cycles)->toBe(1)
        ->and($plan->status)->toBe(PharmacyTreatmentPlanStatus::ACTIVE)
        ->and($plan->next_refill_date?->toDateString())->toBe(now()->addWeek()->toDateString())
        ->and($plan->items[0]->completed_cycles)->toBe(1)
        ->and($prescription->status)->toBe(PrescriptionStatus::PARTIALLY_DISPENSED)
        ->and($prescription->items[0]->status)->toBe(PrescriptionItemStatus::PARTIAL)
        ->and(StockMovement::query()
            ->where('source_document_type', DispensingRecord::class)
            ->where('source_document_id', $record->id)
            ->where('movement_type', StockMovementType::Dispense)
            ->count())->toBe(1);

    $response->assertRedirect(route('pharmacy.treatment-plans.show', $plan));
});
