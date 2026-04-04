<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryItemType;
use App\Enums\InventoryLocationType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createGRTestContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'GR Test Country '.$sequence,
        'country_code' => 'GR'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'GR Test Package '.$sequence,
        'users' => 30 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'GR Test Tenant '.$sequence,
        'domain' => 'gr-test-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'GR'.$sequence.'X',
        'name' => 'GR Test Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Test Branch '.$sequence,
        'branch_code' => 'GB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'gr.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Supplier '.$sequence,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Test Drug '.$sequence,
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'GR Main Store '.$sequence,
        'location_code' => 'GR-MS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'GR-PO-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 5000,
    ]);

    $poItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $po->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 100,
        'unit_cost' => 50,
        'total_cost' => 5000,
    ]);

    $sequence++;

    return [$tenant, $branch, $user, $supplier, $item, $location, $po, $poItem];
}

it('lists goods receipts for authorized user', function (): void {
    [$tenant, $branch, $user, , , $location, $po] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.view');

    GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'GR-00001',
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('goods-receipts.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/goods-receipts/index')
            ->has('goodsReceipts.data', 1));
});

it('denies goods receipt index without permission', function (): void {
    [, $branch, $user] = createGRTestContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('goods-receipts.index'));

    $response->assertForbidden();
});

it('creates a goods receipt', function (): void {
    [$tenant, $branch, $user, , $item, $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo(['goods_receipts.view', 'goods_receipts.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'inventory_location_id' => $location->id,
            'receipt_number' => 'GR-CREATE-001',
            'receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_received' => 50,
                    'unit_cost' => 50,
                    'batch_number' => 'BATCH-001',
                    'expiry_date' => now()->addYear()->toDateString(),
                ],
            ],
        ]);

    $gr = GoodsReceipt::query()->where('receipt_number', 'GR-CREATE-001')->first();
    expect($gr)->not->toBeNull()
        ->and($gr->status)->toBe(GoodsReceiptStatus::Draft)
        ->and($gr->items)->toHaveCount(1);

    $response->assertRedirect(route('goods-receipts.show', $gr));
});

it('posts a goods receipt and updates PO item quantities', function (): void {
    [$tenant, $branch, $user, , $item, $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.update');

    $gr = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'GR-POST-001',
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $gr->id,
        'purchase_order_item_id' => $poItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 50,
        'unit_cost' => 50,
        'batch_number' => 'BATCH-002',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.post', $gr));

    $response->assertRedirect(route('goods-receipts.show', $gr));

    $gr->refresh();
    expect($gr->status)->toBe(GoodsReceiptStatus::Posted)
        ->and($gr->posted_by)->not->toBeNull()
        ->and($gr->posted_at)->not->toBeNull();

    $poItem->refresh();
    expect((float) $poItem->quantity_received)->toBe(50.0);

    $po->refresh();
    expect($po->status)->toBe(PurchaseOrderStatus::Partial);
});

it('fully receiving all items marks PO as received', function (): void {
    [$tenant, $branch, $user, , $item, $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.update');

    $gr = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'GR-FULL-001',
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $gr->id,
        'purchase_order_item_id' => $poItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 100,
        'unit_cost' => 50,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.post', $gr));

    $poItem->refresh();
    expect((float) $poItem->quantity_received)->toBe(100.0);

    $po->refresh();
    expect($po->status)->toBe(PurchaseOrderStatus::Received);
});

it('prevents posting an already posted goods receipt', function (): void {
    [$tenant, $branch, $user, , , $location, $po] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.update');

    $gr = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'GR-NOPOST-001',
        'status' => GoodsReceiptStatus::Posted,
        'receipt_date' => now(),
        'posted_at' => now(),
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.post', $gr));

    $response->assertStatus(422);
});

it('prevents creating goods receipt against a draft PO', function (): void {
    [$tenant, $branch, $user, $supplier, $item, $location] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.create');

    $draftPO = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'GR-DRAFT-PO-001',
        'status' => PurchaseOrderStatus::Draft,
        'order_date' => now(),
        'total_amount' => 0,
    ]);

    $draftPoItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $draftPO->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 10,
        'unit_cost' => 10,
        'total_cost' => 100,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.store'), [
            'purchase_order_id' => $draftPO->id,
            'inventory_location_id' => $location->id,
            'receipt_number' => 'GR-NODRAFT-001',
            'receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $draftPoItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_received' => 10,
                    'unit_cost' => 10,
                ],
            ],
        ]);

    $response->assertStatus(422);
});

it('shows a goods receipt detail page', function (): void {
    [$tenant, $branch, $user, , $item, $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.view');

    $gr = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'GR-SHOW-001',
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $gr->id,
        'purchase_order_item_id' => $poItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 25,
        'unit_cost' => 50,
        'batch_number' => 'BATCH-SHOW',
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('goods-receipts.show', $gr));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/goods-receipts/show')
            ->has('goodsReceipt')
            ->where('goodsReceipt.receipt_number', 'GR-SHOW-001')
            ->has('goodsReceipt.items', 1));
});
