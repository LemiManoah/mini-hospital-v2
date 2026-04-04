<?php

declare(strict_types=1);

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
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

function createPOTestContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'PO Test Country '.$sequence,
        'country_code' => 'PO'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'PO Test Package '.$sequence,
        'users' => 30 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'PO Test Tenant '.$sequence,
        'domain' => 'po-test-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'PO'.$sequence.'X',
        'name' => 'PO Test Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'PO Test Branch '.$sequence,
        'branch_code' => 'PB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'po.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'PO Supplier '.$sequence,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Test Drug '.$sequence,
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $sequence++;

    return [$tenant, $branch, $user, $supplier, $item];
}

it('lists purchase orders for authorized user', function (): void {
    [$tenant, $branch, $user, $supplier] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.view');

    PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-00001',
        'status' => PurchaseOrderStatus::Draft,
        'order_date' => now(),
        'total_amount' => 0,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('purchase-orders.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/purchase-orders/index')
            ->has('purchaseOrders.data', 1));
});

it('denies purchase order index without permission', function (): void {
    [, $branch, $user] = createPOTestContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('purchase-orders.index'));

    $response->assertForbidden();
});

it('creates a purchase order with items', function (): void {
    [$tenant, $branch, $user, $supplier, $item] = createPOTestContext();
    $user->givePermissionTo(['purchase_orders.view', 'purchase_orders.create']);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity_ordered' => 100,
                    'unit_cost' => 50.00,
                ],
            ],
        ]);

    $po = PurchaseOrder::query()->latest('created_at')->first();
    expect($po)->not->toBeNull()
        ->and($po->status)->toBe(PurchaseOrderStatus::Draft)
        ->and((bool) preg_match('/^PO-\d{14}-[A-Z0-9]{4}$/', (string) $po->order_number))->toBeTrue()
        ->and((float) $po->total_amount)->toBe(5000.00)
        ->and($po->items)->toHaveCount(1);

    $response->assertRedirect(route('purchase-orders.show', $po));
});

it('submits a draft purchase order', function (): void {
    [$tenant, $branch, $user, $supplier, $item] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.update');

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-SUBMIT-001',
        'status' => PurchaseOrderStatus::Draft,
        'order_date' => now(),
        'total_amount' => 5000,
    ]);

    PurchaseOrderItem::query()->create([
        'purchase_order_id' => $po->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 100,
        'unit_cost' => 50,
        'total_cost' => 5000,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('purchase-orders.submit', $po));

    $response->assertRedirect(route('purchase-orders.show', $po));
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Submitted);
});

it('approves a submitted purchase order', function (): void {
    [$tenant, $branch, $user, $supplier] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.update');

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-APPROVE-001',
        'status' => PurchaseOrderStatus::Submitted,
        'order_date' => now(),
        'total_amount' => 5000,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('purchase-orders.approve', $po));

    $response->assertRedirect(route('purchase-orders.show', $po));

    $po->refresh();
    expect($po->status)->toBe(PurchaseOrderStatus::Approved)
        ->and($po->approved_by)->not->toBeNull()
        ->and($po->approved_at)->not->toBeNull();
});

it('cancels a purchase order', function (): void {
    [$tenant, $branch, $user, $supplier] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.update');

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-CANCEL-001',
        'status' => PurchaseOrderStatus::Draft,
        'order_date' => now(),
        'total_amount' => 0,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('purchase-orders.cancel', $po));

    $response->assertRedirect(route('purchase-orders.show', $po));
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);
});

it('prevents submitting a non-draft purchase order', function (): void {
    [$tenant, $branch, $user, $supplier] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.update');

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-NODRAFT-001',
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 5000,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('purchase-orders.submit', $po));

    $response->assertStatus(422);
});

it('prevents editing a non-draft purchase order', function (): void {
    [$tenant, $branch, $user, $supplier] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.update');

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-NOEDIT-001',
        'status' => PurchaseOrderStatus::Submitted,
        'order_date' => now(),
        'total_amount' => 5000,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('purchase-orders.edit', $po));

    $response->assertStatus(422);
});

it('shows a purchase order detail page', function (): void {
    [$tenant, $branch, $user, $supplier, $item] = createPOTestContext();
    $user->givePermissionTo('purchase_orders.view');

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-SHOW-001',
        'status' => PurchaseOrderStatus::Draft,
        'order_date' => now(),
        'total_amount' => 5000,
    ]);

    PurchaseOrderItem::query()->create([
        'purchase_order_id' => $po->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 100,
        'unit_cost' => 50,
        'total_cost' => 5000,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('purchase-orders.show', $po));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inventory/purchase-orders/show')
            ->has('purchaseOrder')
            ->where('purchaseOrder.order_number', 'PO-SHOW-001')
            ->has('purchaseOrder.items', 1));
});
