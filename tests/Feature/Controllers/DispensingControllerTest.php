<?php

declare(strict_types=1);

use App\Enums\DispensingRecordStatus;
use App\Enums\PrescriptionItemStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\StockMovementType;
use App\Models\DispensingRecord;
use App\Models\DispensingRecordItemAllocation;
use App\Models\StockMovement;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

require_once __DIR__.'/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('shows the dispense record creation page for a pharmacy prescription', function (): void {
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
    ] = createPharmacyModuleContext();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 4,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-create',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.dispenses.create', $prescription))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/dispenses/create')
            ->where('prescription.id', $prescription->id)
            ->where('dispensingLocations.0.id', $pharmacyLocation->id)
            ->where('defaults.inventory_location_id', $pharmacyLocation->id)
            ->where('pharmacyPolicy.batch_tracking_enabled', true)
            ->where('pharmacyPolicy.allow_partial_dispense', true)
            ->where('prescription.items.0.available_quantity', 30.0));
});

it('creates a dispensing record with item snapshots and balances', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        $pharmacyLocation,
        ,
        ,
        $readyDrug,
        $partialDrug,
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
                'quantity' => 5,
            ],
            [
                'inventory_item_id' => $partialDrug->id,
                'quantity' => 4,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-store',
    );

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.store', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Prepared for patient handover.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 5,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => 'Full quantity available.',
                ],
                [
                    'prescription_item_id' => $prescription->items[1]->id,
                    'dispensed_quantity' => 2,
                    'external_pharmacy' => true,
                    'external_reason' => 'Remainder to be sourced externally.',
                    'notes' => 'Partial local stock.',
                ],
            ],
        ]);

    $record = DispensingRecord::query()
        ->with('items')
        ->latest('created_at')
        ->first();

    expect($record)->not->toBeNull()
        ->and($record?->prescription_id)->toBe($prescription->id)
        ->and($record?->inventory_location_id)->toBe($pharmacyLocation->id)
        ->and($record?->status)->toBe(DispensingRecordStatus::DRAFT)
        ->and($record?->items)->toHaveCount(2)
        ->and($record?->items[0]->dispense_status)->toBe(PrescriptionItemStatus::DISPENSED)
        ->and((float) $record?->items[0]->balance_quantity)->toBe(0.0)
        ->and($record?->items[1]->dispense_status)->toBe(PrescriptionItemStatus::PARTIAL)
        ->and((float) $record?->items[1]->balance_quantity)->toBe(2.0)
        ->and($record?->items[1]->external_pharmacy)->toBeTrue();

    $response->assertRedirect(route('pharmacy.dispenses.show', ['dispensingRecord' => $record]));
});

it('validates dispense requests against dispensing points and line quantities', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        ,
        $pharmacyBackStore,
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
                'quantity' => 2,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-invalid',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.store', $prescription), [
            'inventory_location_id' => $pharmacyBackStore->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 3,
                    'external_pharmacy' => true,
                    'external_reason' => '',
                    'notes' => '',
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'inventory_location_id',
            'items.0.dispensed_quantity',
            'items.0.external_reason',
        ]);
});

it('shows a saved dispensing record and keeps it scoped to the active branch', function (): void {
    [
        $branch,
        $otherBranch,
        $user,
        $staff,
        $pharmacyLocation,
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
                'quantity' => 2,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-show',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.store', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 2,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => '',
                ],
            ],
        ]);

    $record = DispensingRecord::query()->latest('created_at')->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.dispenses.show', ['dispensingRecord' => $record]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/dispenses/show')
            ->where('dispensingRecord.id', $record->id)
            ->where('dispensingRecord.items.0.dispense_status', PrescriptionItemStatus::DISPENSED->value));

    $this->withSession(['active_branch_id' => $otherBranch->id])
        ->actingAs($user)
        ->get(route('pharmacy.dispenses.show', ['dispensingRecord' => $record]))
        ->assertNotFound();
});

it('posts a dispensing record with manual batch allocations and updates prescription statuses', function (): void {
    [
        $branch,
        ,
        $user,
        $staff,
        $pharmacyLocation,
        ,
        ,
        $readyDrug,
        $partialDrug,
        ,
        $readyDrugBatch,
        $partialDrugBatch,
    ] = createPharmacyModuleContext();

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 5,
            ],
            [
                'inventory_item_id' => $partialDrug->id,
                'quantity' => 4,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-post-manual',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.store', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Prepared for manual posting.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 5,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => 'Full quantity available.',
                ],
                [
                    'prescription_item_id' => $prescription->items[1]->id,
                    'dispensed_quantity' => 2,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => 'Partial quantity available.',
                ],
            ],
        ]);

    $record = DispensingRecord::query()->with('items')->latest('created_at')->firstOrFail();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.post', ['dispensingRecord' => $record]), [
            'items' => [
                [
                    'dispensing_record_item_id' => $record->items[0]->id,
                    'allocations' => [
                        [
                            'inventory_batch_id' => $readyDrugBatch->id,
                            'quantity' => 5,
                        ],
                    ],
                ],
                [
                    'dispensing_record_item_id' => $record->items[1]->id,
                    'allocations' => [
                        [
                            'inventory_batch_id' => $partialDrugBatch->id,
                            'quantity' => 2,
                        ],
                    ],
                ],
            ],
        ]);

    $record->refresh()->load('items.allocations');
    $prescription->refresh()->load('items');

    expect($record->status)->toBe(DispensingRecordStatus::POSTED)
        ->and(DispensingRecordItemAllocation::query()->whereIn('dispensing_record_item_id', $record->items->pluck('id'))->count())->toBe(2)
        ->and(StockMovement::query()
            ->where('source_document_type', DispensingRecord::class)
            ->where('source_document_id', $record->id)
            ->where('movement_type', StockMovementType::Dispense)
            ->count())->toBe(2)
        ->and($prescription->status)->toBe(PrescriptionStatus::PARTIALLY_DISPENSED)
        ->and($prescription->items[0]->status)->toBe(PrescriptionItemStatus::DISPENSED)
        ->and($prescription->items[0]->dispensed_at)->not->toBeNull()
        ->and($prescription->items[1]->status)->toBe(PrescriptionItemStatus::PARTIAL);

    $response->assertRedirect(route('pharmacy.dispenses.show', ['dispensingRecord' => $record]));
});

it('auto allocates available pharmacy batches when batch tracking is disabled', function (): void {
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

    storePharmacyGeneralSetting(
        $branch->tenant,
        'pharmacy.enable_batch_tracking_when_dispensing',
        '0',
    );

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 3,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-post-auto',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.store', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Prepared for auto allocation.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 3,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => '',
                ],
            ],
        ]);

    $record = DispensingRecord::query()->with('items')->latest('created_at')->firstOrFail();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.post', ['dispensingRecord' => $record]), [])
        ->assertRedirect(route('pharmacy.dispenses.show', ['dispensingRecord' => $record]));

    $record->refresh()->load('items.allocations');
    $prescription->refresh()->load('items');

    expect($record->status)->toBe(DispensingRecordStatus::POSTED)
        ->and($record->items[0]->allocations)->toHaveCount(1)
        ->and($record->items[0]->allocations[0]->inventory_batch_id)->toBe($readyDrugBatch->id)
        ->and($prescription->status)->toBe(PrescriptionStatus::FULLY_DISPENSED)
        ->and($prescription->items[0]->status)->toBe(PrescriptionItemStatus::DISPENSED);
});

it('blocks partial dispense drafts when partial dispensing is disabled', function (): void {
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
    ] = createPharmacyModuleContext();

    storePharmacyGeneralSetting(
        $branch->tenant,
        'pharmacy.allow_partial_dispense',
        '0',
    );

    $prescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 4,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-no-partial',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.dispenses.store', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 2,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => '',
                ],
            ],
        ])
        ->assertSessionHasErrors([
            'items.0.dispensed_quantity',
        ]);
});

it('dispenses directly from the pharmacy queue flow and posts stock immediately', function (): void {
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
                'quantity' => 4,
            ],
        ],
        PrescriptionStatus::PENDING,
        'queue-direct-dispense',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->withHeader('referer', route('pharmacy.queue.index'))
        ->actingAs($user)
        ->post(route('pharmacy.prescriptions.dispense', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Dispensed directly from the queue.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
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
        ])
        ->assertRedirect(route('pharmacy.queue.index'));

    $record = DispensingRecord::query()->with('items.allocations')->latest('created_at')->firstOrFail();
    $prescription->refresh()->load('items');

    expect($record->status)->toBe(DispensingRecordStatus::POSTED)
        ->and($record->inventory_location_id)->toBe($pharmacyLocation->id)
        ->and($record->items[0]->allocations)->toHaveCount(1)
        ->and($record->items[0]->allocations[0]->inventory_batch_id)->toBe($readyDrugBatch->id)
        ->and($prescription->status)->toBe(PrescriptionStatus::FULLY_DISPENSED)
        ->and($prescription->items[0]->status)->toBe(PrescriptionItemStatus::DISPENSED)
        ->and(StockMovement::query()
            ->where('source_document_type', DispensingRecord::class)
            ->where('source_document_id', $record->id)
            ->where('movement_type', StockMovementType::Dispense)
            ->count())->toBe(1);
});
