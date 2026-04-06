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
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
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

    $gr = GoodsReceipt::query()->latest('created_at')->first();
    expect($gr)->not->toBeNull()
        ->and($gr->status)->toBe(GoodsReceiptStatus::Draft)
        ->and((bool) preg_match('/^GR-\d{14}-[A-Z0-9]{4}$/', (string) $gr->receipt_number))->toBeTrue()
        ->and($gr->items)->toHaveCount(1);

    $response->assertRedirect(route('goods-receipts.show', $gr));
});

it('rejects receipt items from a different purchase order', function (): void {
    [$tenant, $branch, $user, $supplier, $item, $location, $po] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.create');

    $otherPurchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'GR-PO-OTHER-001',
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 2500,
    ]);

    $otherPurchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $otherPurchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 50,
        'unit_cost' => 50,
        'total_cost' => 2500,
    ]);

    $response = $this->from(route('goods-receipts.create'))
        ->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'inventory_location_id' => $location->id,
            'receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $otherPurchaseOrderItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_received' => 10,
                    'unit_cost' => 50,
                ],
            ],
        ]);

    $response->assertRedirect(route('goods-receipts.create'))
        ->assertSessionHasErrors(['items.0.purchase_order_item_id']);

    expect(GoodsReceipt::query()->count())->toBe(0);
});

it('rejects receipt items whose inventory item does not match the purchase order item', function (): void {
    [$tenant, $branch, $user, , , $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.create');

    $otherInventoryItem = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Other Test Drug',
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $response = $this->from(route('goods-receipts.create'))
        ->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'inventory_location_id' => $location->id,
            'receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $otherInventoryItem->id,
                    'quantity_received' => 10,
                    'unit_cost' => 50,
                ],
            ],
        ]);

    $response->assertRedirect(route('goods-receipts.create'))
        ->assertSessionHasErrors(['items.0.inventory_item_id']);

    expect(GoodsReceipt::query()->count())->toBe(0);
});

it('prevents creating another goods receipt while a draft receipt already exists for the purchase order', function (): void {
    [$tenant, $branch, $user, , $item, $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.create');

    $draftReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'GR-DRAFT-LOCK-001',
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $draftReceipt->id,
        'purchase_order_item_id' => $poItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 10,
        'unit_cost' => 50,
    ]);

    $response = $this->from(route('goods-receipts.create'))
        ->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'inventory_location_id' => $location->id,
            'receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_received' => 5,
                    'unit_cost' => 50,
                ],
            ],
        ]);

    $response->assertRedirect(route('goods-receipts.create'))
        ->assertSessionHasErrors(['purchase_order_id']);

    expect(GoodsReceipt::query()->count())->toBe(1);
});

it('prevents receiving more than the remaining purchase order quantity', function (): void {
    [$tenant, $branch, $user, , $item, $location, $po, $poItem] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.create');

    $poItem->update(['quantity_received' => 95]);

    $response = $this->from(route('goods-receipts.create'))
        ->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('goods-receipts.store'), [
            'purchase_order_id' => $po->id,
            'inventory_location_id' => $location->id,
            'receipt_date' => now()->toDateString(),
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $item->id,
                    'quantity_received' => 10,
                    'unit_cost' => 50,
                ],
            ],
        ]);

    $response->assertRedirect(route('goods-receipts.create'))
        ->assertSessionHasErrors(['items.0.quantity_received']);

    expect(GoodsReceipt::query()->count())->toBe(0);
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
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('inventory/goods-receipts/show')
            ->has('goodsReceipt')
            ->where('goodsReceipt.receipt_number', 'GR-SHOW-001')
            ->has('goodsReceipt.items', 1));
});

it('limits receiving locations for pharmacy users to pharmacy stores', function (): void {
    [$tenant, $branch, $user, , , $location, $po] = createGRTestContext();
    $user->assignRole('pharmacist');

    $pharmacyLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'GR Pharmacy Scope',
        'location_code' => 'GR-PHARM',
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('goods-receipts.create', ['purchase_order_id' => $po->id]));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('inventoryLocations', [
                [
                    'id' => $pharmacyLocation->id,
                    'name' => $pharmacyLocation->name,
                    'location_code' => $pharmacyLocation->location_code,
                ],
            ]));

    expect($location->id)->not->toBe($pharmacyLocation->id);
});

it('shows only laboratory receiving locations on the dedicated laboratory receipt create page', function (): void {
    [$tenant, $branch, $user, , , , $po] = createGRTestContext();
    $user->givePermissionTo('goods_receipts.create');

    InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'GR Main Store Hidden',
        'location_code' => 'GR-MAIN-H',
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $labLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'GR Lab Store',
        'location_code' => 'GR-LAB',
        'type' => InventoryLocationType::LABORATORY,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.receipts.create', ['purchase_order_id' => $po->id]));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/receipts/create')
            ->where('inventoryLocations', [
                [
                    'id' => $labLocation->id,
                    'name' => $labLocation->name,
                    'location_code' => $labLocation->location_code,
                ],
            ])
            ->where('navigation.receipts_title', 'Lab Receipts'));
});
