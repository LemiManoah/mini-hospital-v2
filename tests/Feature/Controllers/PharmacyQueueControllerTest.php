<?php

declare(strict_types=1);

use App\Enums\PrescriptionStatus;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

require_once __DIR__.'/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('shows only active branch queue prescriptions with stock availability signals', function (): void {
    [
        $branch,
        $otherBranch,
        $user,
        $staff,
        $pharmacyLocation,
        ,
        ,
        $readyDrug,
        $partialDrug,
        $outOfStockDrug,
    ] = createPharmacyModuleContext();

    createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 1,
            ],
        ],
        PrescriptionStatus::PARTIALLY_DISPENSED,
        'partial-queue',
    );

    createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 10,
            ],
            [
                'inventory_item_id' => $partialDrug->id,
                'quantity' => 5,
            ],
            [
                'inventory_item_id' => $outOfStockDrug->id,
                'quantity' => 3,
            ],
        ],
        PrescriptionStatus::PENDING,
        'pending-queue',
    );

    createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 1,
            ],
        ],
        PrescriptionStatus::FULLY_DISPENSED,
        'done-queue',
    );

    createPharmacyPrescription(
        $otherBranch,
        $otherBranch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 1,
            ],
        ],
        PrescriptionStatus::PENDING,
        'other-branch',
    );

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.queue.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/queue')
            ->where('dispensingLocations.0.id', $pharmacyLocation->id)
            ->where('pharmacyPolicy.batch_tracking_enabled', true)
            ->where('availableBatchBalances.0.inventory_location_id', $pharmacyLocation->id)
            ->has('prescriptions.data', 2)
            ->where('prescriptions.data', function (array $rows): bool {
                if (count($rows) !== 2) {
                    return false;
                }

                $statuses = array_column($rows, 'status');
                sort($statuses);

                $pendingRow = collect($rows)->firstWhere('status', PrescriptionStatus::PENDING->value);
                $partialRow = collect($rows)->firstWhere('status', PrescriptionStatus::PARTIALLY_DISPENSED->value);

                return $statuses === [
                    PrescriptionStatus::PARTIALLY_DISPENSED->value,
                    PrescriptionStatus::PENDING->value,
                ]
                    && is_array($pendingRow)
                    && ($pendingRow['availability']['ready_items'] ?? null) === 1
                    && ($pendingRow['availability']['partial_items'] ?? null) === 1
                    && ($pendingRow['availability']['out_of_stock_items'] ?? null) === 1
                    && ($pendingRow['items'][0]['stock_status'] ?? null) === 'ready'
                    && ($pendingRow['items'][1]['stock_status'] ?? null) === 'partial'
                    && ($pendingRow['items'][2]['stock_status'] ?? null) === 'out_of_stock'
                    && is_array($partialRow);
            }));
});

it('filters the pharmacy queue by search and status', function (): void {
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

    createPharmacyPrescription(
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
        'unique-pending',
    );

    createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 1,
            ],
        ],
        PrescriptionStatus::PARTIALLY_DISPENSED,
        'unique-partial',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.queue.index', ['search' => 'unique-pending']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('filters.search', 'unique-pending')
            ->has('prescriptions.data', 1)
            ->where('prescriptions.data.0.patient.patient_number', 'PAT-PH-unique-pending'));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.queue.index', ['status' => PrescriptionStatus::PARTIALLY_DISPENSED->value]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('filters.status', PrescriptionStatus::PARTIALLY_DISPENSED->value)
            ->has('prescriptions.data', 1)
            ->where('prescriptions.data.0.status', PrescriptionStatus::PARTIALLY_DISPENSED->value));
});

it('shows only the remaining quantity after a partial local dispense and removes fully external prescriptions from the queue', function (): void {
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

    $partialPrescription = createPharmacyPrescription(
        $branch,
        $branch->tenant,
        $user,
        $staff,
        [
            [
                'inventory_item_id' => $readyDrug->id,
                'quantity' => 10,
            ],
        ],
        PrescriptionStatus::PENDING,
        'queue-remaining-local',
    );

    $externalPrescription = createPharmacyPrescription(
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
        'queue-remaining-external',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->withHeader('referer', route('pharmacy.queue.index'))
        ->actingAs($user)
        ->post(route('pharmacy.prescriptions.dispense', $partialPrescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'First partial local issue.',
            'items' => [
                [
                    'prescription_item_id' => $partialPrescription->items[0]->id,
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

    $this->withSession(['active_branch_id' => $branch->id])
        ->withHeader('referer', route('pharmacy.queue.index'))
        ->actingAs($user)
        ->post(route('pharmacy.prescriptions.dispense', $externalPrescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Handled outside the facility pharmacy.',
            'items' => [
                [
                    'prescription_item_id' => $externalPrescription->items[0]->id,
                    'dispensed_quantity' => 0,
                    'external_pharmacy' => true,
                    'external_reason' => 'Not available at the facility pharmacy.',
                    'notes' => '',
                    'allocations' => [],
                ],
            ],
        ])
        ->assertRedirect(route('pharmacy.queue.index'));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.queue.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('prescriptions.data', 1)
            ->where('prescriptions.data.0.id', $partialPrescription->id)
            ->where('prescriptions.data.0.items.0.quantity', 10)
            ->where('prescriptions.data.0.items.0.locally_dispensed_quantity', 4)
            ->where('prescriptions.data.0.items.0.remaining_quantity', 6)
            ->where('prescriptions.data.0.items.0.available_quantity', 26));
});

it('shows the pharmacy prescription review page with line availability and dispensing locations', function (): void {
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
                'quantity' => 3,
            ],
            [
                'inventory_item_id' => $partialDrug->id,
                'quantity' => 5,
            ],
        ],
        PrescriptionStatus::PENDING,
        'detail-view',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.prescriptions.show', $prescription))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/prescriptions/show')
            ->where('prescription.id', $prescription->id)
            ->where('dispensingLocations.0.id', $pharmacyLocation->id)
            ->where('prescription.items.0.stock_status', 'ready')
            ->where('prescription.items.1.stock_status', 'partial'));
});

it('forbids the pharmacy queue without visit view permission', function (): void {
    [$branch, , , $staff] = createPharmacyModuleContext();

    $user = User::query()->create([
        'tenant_id' => $branch->tenant_id,
        'staff_id' => $staff->id,
        'email' => 'pharmacy.noaccess@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.queue.index'))
        ->assertForbidden();
});
