<?php

declare(strict_types=1);

use App\Actions\PostGoodsReceipt;
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

function createInventoryMovementReportContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Movement Country '.$sequence,
        'country_code' => 'MV'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Movement Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Movement Tenant '.$sequence,
        'domain' => 'movement-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'MV'.$sequence,
        'name' => 'Movement Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Movement Branch '.$sequence,
        'branch_code' => 'MVB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $otherBranch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Movement Other Branch '.$sequence,
        'branch_code' => 'MVO'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => false,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'movement.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Movement Supplier '.$sequence,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Movement Main Store '.$sequence,
        'location_code' => 'MVS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $otherLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'name' => 'Movement Other Store '.$sequence,
        'location_code' => 'MVO'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Movement Item '.$sequence,
        'item_type' => InventoryItemType::CONSUMABLE,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'MV-PO-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 400,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 8,
        'unit_cost' => 50,
        'total_cost' => 400,
    ]);

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'MV-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 8,
        'unit_cost' => 50,
        'batch_number' => 'MV-BATCH-'.$sequence,
    ]);

    $otherPurchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'MV-PO-OTHER-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 200,
    ]);

    $otherPurchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $otherPurchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 4,
        'unit_cost' => 50,
        'total_cost' => 200,
    ]);

    $otherGoodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'purchase_order_id' => $otherPurchaseOrder->id,
        'inventory_location_id' => $otherLocation->id,
        'receipt_number' => 'MV-GR-OTHER-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $otherGoodsReceipt->id,
        'purchase_order_item_id' => $otherPurchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 4,
        'unit_cost' => 50,
        'batch_number' => 'MV-BATCH-OTHER-'.$sequence,
    ]);

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);
    resolve(PostGoodsReceipt::class)->handle($otherGoodsReceipt);

    $sequence++;

    return [$branch, $user];
}

it('lists stock movements for the active branch only', function (): void {
    [$branch, $user] = createInventoryMovementReportContext();
    $user->givePermissionTo('inventory_items.view');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory.reports.movements.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('inventory/reports/movements/index')
            ->has('movements.data', 1)
            ->where('movements.data.0.item_name', 'Movement Item 1')
            ->where('movements.data.0.location_name', 'Movement Main Store 1')
            ->where('movements.data.0.movement_type', 'receipt')
            ->where('movements.data.0.quantity', 8));
});

it('denies stock movement report without permission', function (): void {
    [$branch, $user] = createInventoryMovementReportContext();

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory.reports.movements.index'));

    $response->assertForbidden();
});

it('shows only pharmacy movements on the dedicated pharmacy movement page', function (): void {
    [$branch, $user] = createInventoryMovementReportContext();
    $user->givePermissionTo('inventory_items.view');

    $tenantId = $user->tenant_id;
    $supplier = Supplier::query()->where('tenant_id', $tenantId)->firstOrFail();
    $item = InventoryItem::query()->where('tenant_id', $tenantId)->firstOrFail();

    $pharmacyLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenantId,
        'branch_id' => $branch->id,
        'name' => 'Movement Pharmacy Store',
        'location_code' => 'MVPH',
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenantId,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'MV-PHARM-001',
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 500,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 10,
        'unit_cost' => 50,
        'total_cost' => 500,
    ]);

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenantId,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'receipt_number' => 'MV-PHARM-GR-001',
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 10,
        'unit_cost' => 50,
        'batch_number' => 'MV-PHARM-BATCH-001',
    ]);

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.movements.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/movements/index')
            ->has('movements.data', 1)
            ->where('movements.data.0.location_name', 'Movement Pharmacy Store')
            ->where('navigation.movements_title', 'Pharmacy Movements'));
});
