<?php

declare(strict_types=1);

use App\Actions\PostGoodsReceipt;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\InventoryRequisitionStatus;
use App\Enums\Priority;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\SubscriptionPackage;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Support\InventoryStockLedger;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createInventoryRequisitionContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Req Country '.$sequence,
        'country_code' => 'RQ'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Req Package '.$sequence,
        'users' => 10 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Req Tenant '.$sequence,
        'domain' => 'requisition-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'RQ'.$sequence,
        'name' => 'Req Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Req Branch '.$sequence,
        'branch_code' => 'RQB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'requisition.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Req Supplier '.$sequence,
        'is_active' => true,
    ]);

    $sourceLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Main Store '.$sequence,
        'location_code' => 'RQS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $destinationLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Pharmacy '.$sequence,
        'location_code' => 'RQP'.$sequence,
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Req Item '.$sequence,
        'item_type' => InventoryItemType::CONSUMABLE,
        'default_purchase_price' => 25,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'REQ-PO-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 200,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 12,
        'unit_cost' => 25,
        'total_cost' => 300,
    ]);

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $sourceLocation->id,
        'receipt_number' => 'REQ-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 12,
        'unit_cost' => 25,
        'batch_number' => 'REQ-BATCH-'.$sequence,
        'expiry_date' => now()->addMonths(12)->toDateString(),
    ]);

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);

    $batch = InventoryBatch::query()->latest('created_at')->firstOrFail();

    $sequence++;

    return [$branch, $user, $sourceLocation, $destinationLocation, $item, $batch];
}

it('shows the requisition create page', function (): void {
    [$branch, $user] = createInventoryRequisitionContext();
    $user->givePermissionTo('inventory_requisitions.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory-requisitions.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/requisitions/create')
            ->has('sourceInventoryLocations', 2)
            ->has('destinationInventoryLocations', 2)
            ->has('inventoryItems', 1)
            ->has('priorityOptions'));
});

it('limits pharmacy users to pharmacy requisition destinations and main store sources', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item] = createInventoryRequisitionContext();
    $user->assignRole('pharmacist');

    $labLocation = InventoryLocation::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'name' => 'Lab Store Scoped',
        'location_code' => 'RQLAB',
        'type' => InventoryLocationType::LABORATORY,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory-requisitions.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sourceInventoryLocations', [
                [
                    'id' => $sourceLocation->id,
                    'name' => $sourceLocation->name,
                    'location_code' => $sourceLocation->location_code,
                ],
            ])
            ->where('destinationInventoryLocations', [
                [
                    'id' => $destinationLocation->id,
                    'name' => $destinationLocation->name,
                    'location_code' => $destinationLocation->location_code,
                ],
            ]));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.store'), [
            'source_inventory_location_id' => $sourceLocation->id,
            'destination_inventory_location_id' => $labLocation->id,
            'requisition_date' => now()->toDateString(),
            'priority' => Priority::ROUTINE->value,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'requested_quantity' => 2,
                ],
            ],
        ])
        ->assertSessionHasErrors(['destination_inventory_location_id']);
});

it('creates a draft requisition', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item] = createInventoryRequisitionContext();
    $user->givePermissionTo(['inventory_requisitions.view', 'inventory_requisitions.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.store'), [
            'source_inventory_location_id' => $sourceLocation->id,
            'destination_inventory_location_id' => $destinationLocation->id,
            'requisition_date' => now()->toDateString(),
            'priority' => Priority::URGENT->value,
            'notes' => 'Need stock for dispensary restock.',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'requested_quantity' => 5,
                    'notes' => 'Dispensary shelf empty.',
                ],
            ],
        ]);

    $requisition = InventoryRequisition::withoutGlobalScopes()
        ->latest('created_at')
        ->first();

    expect($requisition)->not->toBeNull()
        ->and($requisition->status)->toBe(InventoryRequisitionStatus::Draft)
        ->and($requisition->priority)->toBe(Priority::URGENT)
        ->and($requisition->items)->toHaveCount(1)
        ->and((string) $requisition->items->first()->requested_quantity)->toBe('5.000');

    $response->assertRedirect(route('inventory-requisitions.show', $requisition));
});

it('shows only submitted incoming requisitions for the main store queue', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item] = createInventoryRequisitionContext();
    $user->givePermissionTo('inventory_requisitions.view');

    $labLocation = InventoryLocation::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'name' => 'Req Lab Queue',
        'location_code' => 'RQLQ',
        'type' => InventoryLocationType::LABORATORY,
        'is_active' => true,
    ]);

    $otherMainStore = InventoryLocation::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'name' => 'Req Secondary Store',
        'location_code' => 'RQSS',
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    foreach ([
        [
            'number' => 'REQ-INCOMING-PHARM-001',
            'status' => InventoryRequisitionStatus::Submitted,
            'destination_id' => $destinationLocation->id,
            'date' => now()->subDay()->toDateString(),
        ],
        [
            'number' => 'REQ-INCOMING-LAB-001',
            'status' => InventoryRequisitionStatus::Approved,
            'destination_id' => $labLocation->id,
            'date' => now()->toDateString(),
        ],
        [
            'number' => 'REQ-DRAFT-PHARM-001',
            'status' => InventoryRequisitionStatus::Draft,
            'destination_id' => $destinationLocation->id,
            'date' => now()->subDays(2)->toDateString(),
        ],
        [
            'number' => 'REQ-NONQUEUE-001',
            'status' => InventoryRequisitionStatus::Submitted,
            'destination_id' => $otherMainStore->id,
            'date' => now()->subDays(3)->toDateString(),
        ],
    ] as $payload) {
        $requisition = InventoryRequisition::query()->create([
            'tenant_id' => $user->tenant_id,
            'branch_id' => $branch->id,
            'source_inventory_location_id' => $sourceLocation->id,
            'destination_inventory_location_id' => $payload['destination_id'],
            'requisition_number' => $payload['number'],
            'status' => $payload['status'],
            'priority' => Priority::ROUTINE,
            'requisition_date' => $payload['date'],
        ]);

        $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'requested_quantity' => 2,
            'approved_quantity' => 0,
            'issued_quantity' => 0,
        ]);
    }

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory-requisitions.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/requisitions/index')
            ->where('navigation.requisitions_title', 'Incoming Requisitions')
            ->has('requisitions.data', 2)
            ->where('requisitions.data.0.requisition_number', 'REQ-INCOMING-LAB-001')
            ->where('requisitions.data.1.requisition_number', 'REQ-INCOMING-PHARM-001'));
});

it('allows admin users to open an incoming requisition detail page', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item] = createInventoryRequisitionContext();
    $user->assignRole('admin');

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-INCOMING-SHOW-001',
        'status' => InventoryRequisitionStatus::Submitted,
        'priority' => Priority::ROUTINE,
        'requisition_date' => now()->toDateString(),
    ]);

    $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_quantity' => 2,
        'approved_quantity' => 0,
        'issued_quantity' => 0,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory-requisitions.show', $requisition));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/requisitions/show')
            ->where('requisition.source_location.name', $sourceLocation->name)
            ->where('requisition.destination_location.name', $destinationLocation->name)
            ->where('requisition.status', InventoryRequisitionStatus::Submitted->value)
            ->where('navigation.requisitions_title', 'Incoming Requisitions'));
});

it('prevents pharmacists from opening the main store incoming requisition detail page', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item] = createInventoryRequisitionContext();
    $user->assignRole('pharmacist');

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-INCOMING-BLOCK-001',
        'status' => InventoryRequisitionStatus::Submitted,
        'priority' => Priority::ROUTINE,
        'requisition_date' => now()->toDateString(),
    ]);

    $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_quantity' => 2,
        'approved_quantity' => 0,
        'issued_quantity' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory-requisitions.show', $requisition))
        ->assertForbidden();
});

it('submits approves and partially issues a requisition', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item, $batch] = createInventoryRequisitionContext();
    $user->givePermissionTo(['inventory_requisitions.view', 'inventory_requisitions.update']);

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-WORKFLOW-001',
        'status' => InventoryRequisitionStatus::Draft,
        'priority' => Priority::ROUTINE,
        'requisition_date' => now()->toDateString(),
        'notes' => 'Routine replenishment.',
    ]);

    $line = $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_quantity' => 6,
        'approved_quantity' => 0,
        'issued_quantity' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.submit', $requisition))
        ->assertRedirect(route('inventory-requisitions.show', $requisition));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.approve', $requisition), [
            'approval_notes' => 'Approved by store lead.',
            'items' => [
                [
                    'inventory_requisition_item_id' => $line->id,
                    'approved_quantity' => 6,
                ],
            ],
        ])
        ->assertRedirect(route('inventory-requisitions.show', $requisition));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.issue', $requisition), [
            'issued_notes' => 'Issued part of the request.',
            'items' => [
                [
                    'inventory_requisition_item_id' => $line->id,
                    'issue_quantity' => 4,
                    'notes' => 'First batch release.',
                    'allocations' => [
                        [
                            'inventory_batch_id' => $batch->id,
                            'quantity' => 4,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('inventory-requisitions.show', $requisition));

    $requisition->refresh();
    $line->refresh();

    expect($requisition->status)->toBe(InventoryRequisitionStatus::PartiallyIssued)
        ->and((float) $line->approved_quantity)->toBe(6.0)
        ->and((float) $line->issued_quantity)->toBe(4.0);

    $outbound = StockMovement::withoutGlobalScopes()
        ->where('source_document_type', InventoryRequisition::class)
        ->where('source_document_id', $requisition->id)
        ->where('movement_type', StockMovementType::RequisitionOut)
        ->first();
    $inbound = StockMovement::withoutGlobalScopes()
        ->where('source_document_type', InventoryRequisition::class)
        ->where('source_document_id', $requisition->id)
        ->where('movement_type', StockMovementType::RequisitionIn)
        ->first();

    expect($outbound)->not->toBeNull()
        ->and((float) $outbound->quantity)->toBe(-4.0)
        ->and($inbound)->not->toBeNull()
        ->and((float) $inbound->quantity)->toBe(4.0);

    $balances = resolve(InventoryStockLedger::class)->summarizeByLocation($branch->id);
    $sourceBalance = $balances
        ->firstWhere('inventory_location_id', $sourceLocation->id)['quantity'] ?? 0.0;
    $destinationBalance = $balances
        ->firstWhere('inventory_location_id', $destinationLocation->id)['quantity'] ?? 0.0;

    expect((float) $sourceBalance)->toBe(8.0)
        ->and((float) $destinationBalance)->toBe(4.0);
});

it('fulfills a requisition when all approved stock is issued', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item, $batch] = createInventoryRequisitionContext();
    $user->givePermissionTo(['inventory_requisitions.view', 'inventory_requisitions.update']);

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-FULL-001',
        'status' => InventoryRequisitionStatus::Approved,
        'priority' => Priority::ROUTINE,
        'requisition_date' => now()->toDateString(),
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $line = $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_quantity' => 3,
        'approved_quantity' => 3,
        'issued_quantity' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.issue', $requisition), [
            'items' => [
                [
                    'inventory_requisition_item_id' => $line->id,
                    'issue_quantity' => 3,
                    'allocations' => [
                        [
                            'inventory_batch_id' => $batch->id,
                            'quantity' => 3,
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('inventory-requisitions.show', $requisition));

    $requisition->refresh();
    expect($requisition->status)->toBe(InventoryRequisitionStatus::Fulfilled);
});

it('rejects a submitted requisition', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation] = createInventoryRequisitionContext();
    $user->givePermissionTo(['inventory_requisitions.view', 'inventory_requisitions.update']);

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-REJECT-001',
        'status' => InventoryRequisitionStatus::Submitted,
        'priority' => Priority::URGENT,
        'requisition_date' => now()->toDateString(),
        'submitted_by' => $user->id,
        'submitted_at' => now(),
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.reject', $requisition), [
            'rejection_reason' => 'Requested quantity should be revised first.',
        ]);

    $response->assertRedirect(route('inventory-requisitions.show', $requisition));

    $requisition->refresh();

    expect($requisition->status)->toBe(InventoryRequisitionStatus::Rejected)
        ->and($requisition->rejection_reason)
        ->toBe('Requested quantity should be revised first.');
});

it('prevents pharmacy users from approving main store requisitions', function (): void {
    [$branch, $user, $sourceLocation, $destinationLocation, $item] = createInventoryRequisitionContext();
    $user->assignRole('pharmacist');

    $requisition = InventoryRequisition::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'source_inventory_location_id' => $sourceLocation->id,
        'destination_inventory_location_id' => $destinationLocation->id,
        'requisition_number' => 'REQ-SCOPE-001',
        'status' => InventoryRequisitionStatus::Submitted,
        'priority' => Priority::ROUTINE,
        'requisition_date' => now()->toDateString(),
        'submitted_by' => $user->id,
        'submitted_at' => now(),
    ]);

    $line = $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_quantity' => 3,
        'approved_quantity' => 0,
        'issued_quantity' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('inventory-requisitions.approve', $requisition), [
            'items' => [
                [
                    'inventory_requisition_item_id' => $line->id,
                    'approved_quantity' => 3,
                ],
            ],
        ])
        ->assertForbidden();
});

it('shows only laboratory destinations on the dedicated laboratory requisition create page', function (): void {
    [$branch, $user, $sourceLocation] = createInventoryRequisitionContext();
    $user->givePermissionTo('inventory_requisitions.create');

    $labLocation = InventoryLocation::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'name' => 'Req Lab Store',
        'location_code' => 'RQLS',
        'type' => InventoryLocationType::LABORATORY,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.requisitions.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('laboratory/requisitions/create')
            ->where('sourceInventoryLocations', [
                [
                    'id' => $sourceLocation->id,
                    'name' => $sourceLocation->name,
                    'location_code' => $sourceLocation->location_code,
                ],
            ])
            ->where('destinationInventoryLocations', [
                [
                    'id' => $labLocation->id,
                    'name' => $labLocation->name,
                    'location_code' => $labLocation->location_code,
                ],
            ])
            ->where('navigation.requisitions_title', 'Lab Requisitions'));
});
