<?php

declare(strict_types=1);

use App\Actions\PostGoodsReceipt;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\SubscriptionPackage;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createInventoryReconciliationTestContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Reconciliation Country '.$sequence,
        'country_code' => 'RC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Reconciliation Package '.$sequence,
        'users' => 10 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Reconciliation Tenant '.$sequence,
        'domain' => 'reconciliation-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'RC'.$sequence,
        'name' => 'Reconciliation Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Reconciliation Branch '.$sequence,
        'branch_code' => 'RCB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'reconciliation.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Reconciliation Supplier '.$sequence,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Reconciliation Store '.$sequence,
        'location_code' => 'RCS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Reconciliation Item '.$sequence,
        'item_type' => InventoryItemType::CONSUMABLE,
        'default_purchase_price' => 25,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'RC-PO-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 200,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 8,
        'unit_cost' => 25,
        'total_cost' => 200,
    ]);

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'RC-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 8,
        'unit_cost' => 25,
        'batch_number' => 'RC-BATCH-'.$sequence,
        'expiry_date' => now()->addYear()->toDateString(),
    ]);

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);

    $batch = InventoryBatch::query()->latest('created_at')->firstOrFail();

    $sequence++;

    return [$branch, $user, $location, $item, $batch];
}

it('lists reconciliations for authorized user', function (): void {
    [$branch, $user, $location] = createInventoryReconciliationTestContext();
    $user->givePermissionTo('stock_adjustments.view');

    StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'REC-LIST-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Cycle shelf check',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reconciliations.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/reconciliations/index')
            ->has('reconciliations.data', 1)
            ->where('reconciliations.data.0.adjustment_number', 'REC-LIST-001'));
});

it('shows the reconciliation create page with balances', function (): void {
    [$branch, $user] = createInventoryReconciliationTestContext();
    $user->givePermissionTo('stock_adjustments.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reconciliations.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/reconciliations/create')
            ->has('inventoryLocations', 1)
            ->has('inventoryItems', 1)
            ->has('locationBalances', 1)
            ->has('batchBalances', 1));
});

it('creates a draft reconciliation with expected and actual quantities', function (): void {
    [$branch, $user, $location, $item, $batch] = createInventoryReconciliationTestContext();
    $user->givePermissionTo(['stock_adjustments.view', 'stock_adjustments.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('reconciliations.store'), [
            'inventory_location_id' => $location->id,
            'reconciliation_date' => now()->toDateString(),
            'reason' => 'Cycle count reconciliation',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'inventory_batch_id' => $batch->id,
                    'actual_quantity' => 6,
                    'unit_cost' => 25,
                    'notes' => 'Two units missing after shelf check',
                ],
            ],
        ]);

    $reconciliation = StockAdjustment::withoutGlobalScopes()
        ->latest('created_at')
        ->first();

    expect($reconciliation)->not->toBeNull()
        ->and($reconciliation->status)->toBe(StockAdjustmentStatus::Draft)
        ->and((bool) preg_match('/^REC-\d{14}-[A-Z0-9]{4}$/', (string) $reconciliation->adjustment_number))->toBeTrue()
        ->and($reconciliation->items)->toHaveCount(1)
        ->and((string) $reconciliation->items->first()->expected_quantity)->toBe('8.000')
        ->and((string) $reconciliation->items->first()->actual_quantity)->toBe('6.000')
        ->and((string) $reconciliation->items->first()->quantity_delta)->toBe('-2.000');

    $response->assertRedirect(route('reconciliations.show', $reconciliation))
        ->assertSessionHas('reconciliation_prompt', 'submit');

    expect(
        StockMovement::withoutGlobalScopes()
            ->where('source_document_type', StockAdjustment::class)
            ->count()
    )->toBe(0);
});

it('runs the submit review approve and post workflow', function (): void {
    [$branch, $user, $location, $item, $batch] = createInventoryReconciliationTestContext();
    $user->givePermissionTo(['stock_adjustments.view', 'stock_adjustments.update']);

    $reconciliation = StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'REC-WORKFLOW-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Damaged stock on shelf',
    ]);

    $reconciliation->items()->create([
        'inventory_item_id' => $item->id,
        'inventory_batch_id' => $batch->id,
        'expected_quantity' => 8,
        'actual_quantity' => 5,
        'variance_quantity' => -3,
        'quantity_delta' => -3,
        'unit_cost' => 25,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('reconciliations.submit', $reconciliation))
        ->assertRedirect(route('reconciliations.show', $reconciliation));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('reconciliations.review', $reconciliation), [
            'review_notes' => 'Verified with storekeeper.',
        ])
        ->assertRedirect(route('reconciliations.show', $reconciliation));

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('reconciliations.approve', $reconciliation), [
            'approval_notes' => 'Approved for posting.',
        ])
        ->assertRedirect(route('reconciliations.show', $reconciliation))
        ->assertSessionHas('reconciliation_prompt', 'post');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('reconciliations.post', $reconciliation));

    $response->assertRedirect(route('reconciliations.show', $reconciliation));

    $reconciliation->refresh();

    expect($reconciliation->status)->toBe(StockAdjustmentStatus::Posted)
        ->and($reconciliation->submitted_at)->not->toBeNull()
        ->and($reconciliation->reviewed_at)->not->toBeNull()
        ->and($reconciliation->approved_at)->not->toBeNull()
        ->and($reconciliation->posted_at)->not->toBeNull();

    $movement = StockMovement::withoutGlobalScopes()
        ->where('source_document_type', StockAdjustment::class)
        ->where('source_document_id', $reconciliation->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->movement_type)->toBe(StockMovementType::AdjustmentLoss)
        ->and((float) $movement->quantity)->toBe(-3.0);
});

it('allows a submitted reconciliation to be rejected', function (): void {
    [$branch, $user, $location] = createInventoryReconciliationTestContext();
    $user->givePermissionTo('stock_adjustments.update');

    $reconciliation = StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'REC-REJECT-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Pending review',
        'submitted_by' => $user->id,
        'submitted_at' => now(),
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('reconciliations.reject', $reconciliation), [
            'rejection_reason' => 'Please recount the shelf before approval.',
        ]);

    $response->assertRedirect(route('reconciliations.show', $reconciliation));

    $reconciliation->refresh();

    expect($reconciliation->rejected_at)->not->toBeNull()
        ->and($reconciliation->rejection_reason)
        ->toBe('Please recount the shelf before approval.');
});

it('shows a reconciliation detail page', function (): void {
    [$branch, $user, $location, $item, $batch] = createInventoryReconciliationTestContext();
    $user->givePermissionTo('stock_adjustments.view');

    $reconciliation = StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'REC-SHOW-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Store shelf check',
    ]);

    $reconciliation->items()->create([
        'inventory_item_id' => $item->id,
        'inventory_batch_id' => $batch->id,
        'expected_quantity' => 8,
        'actual_quantity' => 8,
        'variance_quantity' => 0,
        'quantity_delta' => 0,
        'unit_cost' => 25,
        'notes' => 'No variance found',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('reconciliations.show', $reconciliation));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/reconciliations/show')
            ->where('reconciliation.adjustment_number', 'REC-SHOW-001')
            ->has('reconciliation.items', 1));
});
