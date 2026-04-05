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

function createStockAdjustmentTestContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Adjustment Country '.$sequence,
        'country_code' => 'SA'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Adjustment Package '.$sequence,
        'users' => 10 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Adjustment Tenant '.$sequence,
        'domain' => 'adjustment-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'SA'.$sequence,
        'name' => 'Adjustment Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Adjustment Branch '.$sequence,
        'branch_code' => 'SAB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'adjustment.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Adjustment Supplier '.$sequence,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Adjustment Store '.$sequence,
        'location_code' => 'SAS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Adjustment Item '.$sequence,
        'item_type' => InventoryItemType::CONSUMABLE,
        'default_purchase_price' => 25,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'SA-PO-'.$sequence,
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
        'receipt_number' => 'SA-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 8,
        'unit_cost' => 25,
        'batch_number' => 'SA-BATCH-'.$sequence,
        'expiry_date' => now()->addYear()->toDateString(),
    ]);

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);

    $batch = InventoryBatch::query()->latest('created_at')->firstOrFail();

    $sequence++;

    return [$branch, $user, $location, $item, $batch];
}

it('lists stock adjustments for authorized user', function (): void {
    [$branch, $user, $location] = createStockAdjustmentTestContext();
    $user->givePermissionTo('stock_adjustments.view');

    StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'ADJ-LIST-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Cycle count correction',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('stock-adjustments.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/adjustments/index')
            ->has('stockAdjustments.data', 1)
            ->where('stockAdjustments.data.0.adjustment_number', 'ADJ-LIST-001'));
});

it('shows the stock adjustment create page with location and batch balances', function (): void {
    [$branch, $user] = createStockAdjustmentTestContext();
    $user->givePermissionTo('stock_adjustments.create');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('stock-adjustments.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/adjustments/create')
            ->has('inventoryLocations', 1)
            ->has('inventoryItems', 1)
            ->has('locationBalances', 1)
            ->has('batchBalances', 1));
});

it('creates a draft stock adjustment', function (): void {
    [$branch, $user, $location, $item, $batch] = createStockAdjustmentTestContext();
    $user->givePermissionTo(['stock_adjustments.view', 'stock_adjustments.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('stock-adjustments.store'), [
            'inventory_location_id' => $location->id,
            'adjustment_date' => now()->toDateString(),
            'reason' => 'Broken stock write-off',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity_delta' => -2,
                    'unit_cost' => 25,
                    'notes' => 'Damaged while unpacking',
                ],
            ],
        ]);

    $adjustment = StockAdjustment::withoutGlobalScopes()
        ->latest('created_at')
        ->first();

    expect($adjustment)->not->toBeNull()
        ->and($adjustment->status)->toBe(StockAdjustmentStatus::Draft)
        ->and((bool) preg_match('/^ADJ-\d{14}-[A-Z0-9]{4}$/', (string) $adjustment->adjustment_number))->toBeTrue()
        ->and($adjustment->items)->toHaveCount(1);

    $response->assertRedirect(route('stock-adjustments.show', $adjustment));
    expect(
        StockMovement::withoutGlobalScopes()
            ->where('source_document_type', StockAdjustment::class)
            ->count()
    )->toBe(0);
});

it('rejects a stock loss larger than the selected batch balance', function (): void {
    [$branch, $user, $location, $item, $batch] = createStockAdjustmentTestContext();
    $user->givePermissionTo('stock_adjustments.create');

    $response = $this->from(route('stock-adjustments.create'))
        ->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('stock-adjustments.store'), [
            'inventory_location_id' => $location->id,
            'adjustment_date' => now()->toDateString(),
            'reason' => 'Shrinkage',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'inventory_batch_id' => $batch->id,
                    'quantity_delta' => -20,
                    'unit_cost' => 25,
                ],
            ],
        ]);

    $response->assertRedirect(route('stock-adjustments.create'))
        ->assertSessionHasErrors(['items.0.quantity_delta']);

    expect(StockAdjustment::withoutGlobalScopes()->count())->toBe(0);
});

it('posts a gain adjustment and creates a movement plus a new batch', function (): void {
    [$branch, $user, $location, $item] = createStockAdjustmentTestContext();
    $user->givePermissionTo(['stock_adjustments.view', 'stock_adjustments.update']);

    $adjustment = StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'ADJ-POST-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Opening balance top-up',
    ]);

    $adjustment->items()->create([
        'inventory_item_id' => $item->id,
        'inventory_batch_id' => null,
        'quantity_delta' => 5,
        'unit_cost' => 30,
        'batch_number' => 'ADJ-BATCH-NEW',
        'expiry_date' => now()->addMonths(6)->toDateString(),
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('stock-adjustments.post', $adjustment));

    $response->assertRedirect(route('stock-adjustments.show', $adjustment));

    $adjustment->refresh();
    expect($adjustment->status)->toBe(StockAdjustmentStatus::Posted)
        ->and($adjustment->posted_at)->not->toBeNull();

    $movement = StockMovement::withoutGlobalScopes()
        ->where('source_document_type', StockAdjustment::class)
        ->where('source_document_id', $adjustment->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->movement_type)->toBe(StockMovementType::AdjustmentGain)
        ->and((float) $movement->quantity)->toBe(5.0)
        ->and($movement->inventory_batch_id)->not->toBeNull();

    $createdBatch = InventoryBatch::withoutGlobalScopes()->find($movement->inventory_batch_id);

    expect($createdBatch)->not->toBeNull()
        ->and($createdBatch->batch_number)->toBe('ADJ-BATCH-NEW');
});

it('shows a stock adjustment detail page', function (): void {
    [$branch, $user, $location, $item, $batch] = createStockAdjustmentTestContext();
    $user->givePermissionTo('stock_adjustments.view');

    $adjustment = StockAdjustment::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $location->id,
        'adjustment_number' => 'ADJ-SHOW-001',
        'status' => StockAdjustmentStatus::Draft,
        'adjustment_date' => now()->toDateString(),
        'reason' => 'Damaged stock',
    ]);

    $adjustment->items()->create([
        'inventory_item_id' => $item->id,
        'inventory_batch_id' => $batch->id,
        'quantity_delta' => -1,
        'unit_cost' => 25,
        'notes' => 'One unit damaged',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('stock-adjustments.show', $adjustment));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/adjustments/show')
            ->where('stockAdjustment.adjustment_number', 'ADJ-SHOW-001')
            ->has('stockAdjustment.items', 1));
});
