<?php

declare(strict_types=1);

use App\Actions\PostGoodsReceipt;
use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockCountStatus;
use App\Enums\StockMovementType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockCount;
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

function createStockCountTestContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Count Country '.$sequence,
        'country_code' => 'SC'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Count Package '.$sequence,
        'users' => 10 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Count Tenant '.$sequence,
        'domain' => 'count-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'SC'.$sequence,
        'name' => 'Count Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Count Branch '.$sequence,
        'branch_code' => 'SCB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'count.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Count Supplier '.$sequence,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Count Store '.$sequence,
        'location_code' => 'SCS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Count Item '.$sequence,
        'item_type' => InventoryItemType::SUPPLY,
        'default_purchase_price' => 40,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'SC-PO-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 400,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 10,
        'unit_cost' => 40,
        'total_cost' => 400,
    ]);

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'SC-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 10,
        'unit_cost' => 40,
        'batch_number' => 'SC-BATCH-'.$sequence,
    ]);

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);

    $sequence++;

    return [$branch, $user, $location, $item];
}

it('lists stock counts for authorized user', function (): void {
    [$branch, $user, $location] = createStockCountTestContext();
    $user->givePermissionTo('stock_counts.view');

    StockCount::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'count_number' => 'CNT-LIST-001',
        'status' => StockCountStatus::Draft,
        'count_date' => now()->toDateString(),
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('stock-counts.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/counts/index')
            ->has('stockCounts.data', 1)
            ->where('stockCounts.data.0.count_number', 'CNT-LIST-001'));
});

it('shows the stock count create page with balances', function (): void {
    [$branch, $user] = createStockCountTestContext();
    $user->givePermissionTo('stock_counts.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('stock-counts.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/counts/create')
            ->has('inventoryLocations', 1)
            ->has('inventoryItems', 1)
            ->has('locationBalances', 1));
});

it('creates a draft stock count using current ledger quantities as expected values', function (): void {
    [$branch, $user, $location, $item] = createStockCountTestContext();
    $user->givePermissionTo(['stock_counts.view', 'stock_counts.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('stock-counts.store'), [
            'inventory_location_id' => $location->id,
            'count_date' => now()->toDateString(),
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'counted_quantity' => 8,
                    'notes' => 'Two units not found on shelf',
                ],
            ],
        ]);

    $stockCount = StockCount::withoutGlobalScopes()
        ->latest('created_at')
        ->first();

    expect($stockCount)->not->toBeNull()
        ->and($stockCount->status)->toBe(StockCountStatus::Draft)
        ->and((bool) preg_match('/^CNT-\d{14}-[A-Z0-9]{4}$/', (string) $stockCount->count_number))->toBeTrue();

    $itemLine = $stockCount->items()->first();

    expect($itemLine)->not->toBeNull()
        ->and((float) $itemLine->expected_quantity)->toBe(10.0)
        ->and((float) $itemLine->counted_quantity)->toBe(8.0)
        ->and((float) $itemLine->variance_quantity)->toBe(-2.0);

    $response->assertRedirect(route('stock-counts.show', $stockCount));
});

it('posts a stock count and creates variance stock movements', function (): void {
    [$branch, $user, $location, $item] = createStockCountTestContext();
    $user->givePermissionTo(['stock_counts.view', 'stock_counts.update']);

    $stockCount = StockCount::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'count_number' => 'CNT-POST-001',
        'status' => StockCountStatus::Draft,
        'count_date' => now()->toDateString(),
    ]);

    $stockCount->items()->create([
        'inventory_item_id' => $item->id,
        'expected_quantity' => 10,
        'counted_quantity' => 7,
        'variance_quantity' => -3,
        'notes' => 'Three units missing',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('stock-counts.post', $stockCount));

    $response->assertRedirect(route('stock-counts.show', $stockCount));

    $stockCount->refresh();
    expect($stockCount->status)->toBe(StockCountStatus::Posted)
        ->and($stockCount->posted_at)->not->toBeNull();

    $movement = StockMovement::withoutGlobalScopes()
        ->where('source_document_type', StockCount::class)
        ->where('source_document_id', $stockCount->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->movement_type)->toBe(StockMovementType::AdjustmentLoss)
        ->and((float) $movement->quantity)->toBe(-3.0);
});

it('prevents posting a stock count after stock moved since the count was recorded', function (): void {
    [$branch, $user, $location, $item] = createStockCountTestContext();
    $user->givePermissionTo('stock_counts.update');

    $stockCount = StockCount::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'count_number' => 'CNT-STALE-001',
        'status' => StockCountStatus::Draft,
        'count_date' => now()->toDateString(),
    ]);

    $stockCount->items()->create([
        'inventory_item_id' => $item->id,
        'expected_quantity' => 10,
        'counted_quantity' => 9,
        'variance_quantity' => -1,
    ]);

    StockMovement::withoutGlobalScopes()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'inventory_item_id' => $item->id,
        'inventory_batch_id' => null,
        'movement_type' => StockMovementType::AdjustmentLoss,
        'quantity' => -1,
        'unit_cost' => 40,
        'source_document_type' => 'manual-test',
        'source_document_id' => $stockCount->id,
        'source_line_type' => 'manual-test-line',
        'source_line_id' => $stockCount->id,
        'notes' => 'Intervening movement',
        'occurred_at' => now(),
        'created_by' => $user->id,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('stock-counts.post', $stockCount));

    $response->assertStatus(422);
});

it('shows a stock count detail page', function (): void {
    [$branch, $user, $location, $item] = createStockCountTestContext();
    $user->givePermissionTo('stock_counts.view');

    $stockCount = StockCount::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'count_number' => 'CNT-SHOW-001',
        'status' => StockCountStatus::Draft,
        'count_date' => now()->toDateString(),
    ]);

    $stockCount->items()->create([
        'inventory_item_id' => $item->id,
        'expected_quantity' => 10,
        'counted_quantity' => 10,
        'variance_quantity' => 0,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('stock-counts.show', $stockCount));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/counts/show')
            ->where('stockCount.count_number', 'CNT-SHOW-001')
            ->has('stockCount.items', 1));
});
