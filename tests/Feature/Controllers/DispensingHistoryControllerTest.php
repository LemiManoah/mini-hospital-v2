<?php

declare(strict_types=1);

use App\Enums\PrescriptionStatus;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

require_once __DIR__.'/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('shows dispense history records with the dispenser name resolved from staff', function (): void {
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
                'quantity' => 2,
            ],
        ],
        PrescriptionStatus::PENDING,
        'dispense-history',
    );

    $this->withSession(['active_branch_id' => $branch->id])
        ->withHeader('referer', route('pharmacy.queue.index'))
        ->actingAs($user)
        ->post(route('pharmacy.prescriptions.dispense', $prescription), [
            'inventory_location_id' => $pharmacyLocation->id,
            'dispensed_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Posted for history listing.',
            'items' => [
                [
                    'prescription_item_id' => $prescription->items[0]->id,
                    'dispensed_quantity' => 2,
                    'external_pharmacy' => false,
                    'external_reason' => '',
                    'notes' => '',
                    'allocations' => [
                        [
                            'inventory_batch_id' => $readyDrugBatch->id,
                            'quantity' => 2,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('pharmacy.queue.index'));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.dispenses.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/dispenses/index')
            ->has('records.data', 1)
            ->where('records.data.0.visit_number', $prescription->visit->visit_number)
            ->where('records.data.0.dispensed_by', mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name))));
});
