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
use App\Models\InventoryLocationItem;
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

function createInventoryStockContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Stock Country '.$sequence,
        'country_code' => 'ST'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Stock Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Stock Tenant '.$sequence,
        'domain' => 'stock-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'ST'.$sequence.'X',
        'name' => 'Stock Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Alpha Branch '.$sequence,
        'branch_code' => 'AB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $otherBranch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Zulu Branch '.$sequence,
        'branch_code' => 'ZB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => false,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'stock.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Stock Supplier '.$sequence,
        'is_active' => true,
    ]);

    $itemA = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Amoxicillin '.$sequence,
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
        'minimum_stock_level' => 2,
        'reorder_level' => 5,
    ]);

    $itemB = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Gloves '.$sequence,
        'item_type' => InventoryItemType::SUPPLY,
        'is_active' => true,
        'minimum_stock_level' => 10,
        'reorder_level' => 15,
    ]);

    $locationA = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Alpha Main Store '.$sequence,
        'location_code' => 'AMS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $locationB = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Beta Pharmacy '.$sequence,
        'location_code' => 'BPH'.$sequence,
        'type' => InventoryLocationType::PHARMACY,
        'is_active' => true,
        'is_dispensing_point' => true,
    ]);

    $otherLocation = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'name' => 'Zulu Store '.$sequence,
        'location_code' => 'ZST'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    InventoryLocationItem::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $locationB->id,
        'inventory_item_id' => $itemB->id,
        'minimum_stock_level' => 8,
        'reorder_level' => 12,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-STOCK-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 1000,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $itemA->id,
        'quantity_ordered' => 20,
        'unit_cost' => 50,
        'total_cost' => 1000,
    ]);

    $postedReceiptOne = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $locationA->id,
        'receipt_number' => 'GR-STOCK-1-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now()->subDay(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $postedReceiptOne->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $itemA->id,
        'quantity_received' => 5,
        'unit_cost' => 50,
    ]);

    $postedReceiptTwo = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $locationA->id,
        'receipt_number' => 'GR-STOCK-2-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $postedReceiptTwo->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $itemA->id,
        'quantity_received' => 3,
        'unit_cost' => 50,
    ]);

    $draftReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $locationA->id,
        'receipt_number' => 'GR-STOCK-DRAFT-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $draftReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $itemA->id,
        'quantity_received' => 7,
        'unit_cost' => 50,
    ]);

    $otherPurchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PO-STOCK-OTHER-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 300,
    ]);

    $otherPurchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $otherPurchaseOrder->id,
        'inventory_item_id' => $itemB->id,
        'quantity_ordered' => 6,
        'unit_cost' => 50,
        'total_cost' => 300,
    ]);

    $otherBranchReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'purchase_order_id' => $otherPurchaseOrder->id,
        'inventory_location_id' => $otherLocation->id,
        'receipt_number' => 'GR-STOCK-OTHER-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $otherBranchReceipt->id,
        'purchase_order_item_id' => $otherPurchaseOrderItem->id,
        'inventory_item_id' => $itemB->id,
        'quantity_received' => 6,
        'unit_cost' => 50,
    ]);

    resolve(PostGoodsReceipt::class)->handle($postedReceiptOne);
    resolve(PostGoodsReceipt::class)->handle($postedReceiptTwo);
    resolve(PostGoodsReceipt::class)->handle($otherBranchReceipt);

    $sequence++;

    return [
        'branch' => $branch,
        'user' => $user,
        'locationA' => $locationA,
        'locationB' => $locationB,
    ];
}

it('lists stock by location using posted receipts and configured location items', function (): void {
    $context = createInventoryStockContext();
    $branch = $context['branch'];
    $user = $context['user'];
    $locationA = $context['locationA'];
    $locationB = $context['locationB'];
    $user->givePermissionTo('inventory_items.view');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory.stock-by-location.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('inventory/stock-by-location/index')
            ->has('locations', 2)
            ->where('locations.0.id', $locationA->id)
            ->where('locations.0.name', 'Alpha Main Store 1')
            ->where('locations.1.id', $locationB->id)
            ->where('locations.1.name', 'Beta Pharmacy 1')
            ->has('rows.data', 2)
            ->where('rows.data.0.item_name', 'Amoxicillin 1')
            ->where('rows.data.0.location_quantities.'.$locationA->id, 8)
            ->where('rows.data.0.location_quantities.'.$locationB->id, 0)
            ->where('rows.data.0.total_quantity', 8)
            ->where('rows.data.1.item_name', 'Gloves 1')
            ->where('rows.data.1.location_quantities.'.$locationA->id, 0)
            ->where('rows.data.1.location_quantities.'.$locationB->id, 0)
            ->where('rows.data.1.total_quantity', 0));
});

it('denies stock by location page without permission', function (): void {
    $context = createInventoryStockContext();
    $branch = $context['branch'];
    $user = $context['user'];

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory.stock-by-location.index'));

    $response->assertForbidden();
});

it('shows only pharmacy-managed stock locations for pharmacists', function (): void {
    $context = createInventoryStockContext();
    $branch = $context['branch'];
    $user = $context['user'];
    $locationB = $context['locationB'];

    $user->assignRole('pharmacist');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('inventory.stock-by-location.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('locations', 1)
            ->where('locations.0.id', $locationB->id)
            ->has('rows.data', 1)
            ->where('rows.data.0.location_quantities.'.$locationB->id, 0));
});

it('shows all inventory items on the dedicated pharmacy stock page while keeping quantities pharmacy scoped', function (): void {
    $context = createInventoryStockContext();
    $branch = $context['branch'];
    $user = $context['user'];
    $locationB = $context['locationB'];

    $user->assignRole('pharmacist');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.stock.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/stock/index')
            ->has('locations', 1)
            ->where('locations.0.id', $locationB->id)
            ->has('rows.data', 2)
            ->where('rows.data.0.item_name', 'Amoxicillin 4')
            ->where('rows.data.0.location_quantities.'.$locationB->id, 0)
            ->where('rows.data.0.total_quantity', 0)
            ->where('rows.data.1.item_name', 'Gloves 4')
            ->where('rows.data.1.location_quantities.'.$locationB->id, 0)
            ->where('rows.data.1.total_quantity', 0)
            ->where('navigation.stock_title', 'Pharmacy Stock'));
});

it('shows only laboratory locations on the dedicated laboratory stock page', function (): void {
    $context = createInventoryStockContext();
    $branch = $context['branch'];
    $user = $context['user'];
    $user->givePermissionTo('inventory_items.view');

    $labLocation = InventoryLocation::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'name' => 'Gamma Lab Store',
        'location_code' => 'GLS1',
        'type' => InventoryLocationType::LABORATORY,
        'is_active' => true,
    ]);

    InventoryLocationItem::query()->create([
        'tenant_id' => $user->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $labLocation->id,
        'inventory_item_id' => InventoryItem::query()->where('tenant_id', $user->tenant_id)->firstOrFail()->id,
        'minimum_stock_level' => 2,
        'reorder_level' => 4,
        'is_active' => true,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('laboratory.stock.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('laboratory/stock/index')
            ->has('locations', 1)
            ->where('locations.0.id', $labLocation->id)
            ->has('rows.data', 2)
            ->where('rows.data.0.location_quantities.'.$labLocation->id, 0)
            ->where('rows.data.1.location_quantities.'.$labLocation->id, 0)
            ->where('navigation.stock_title', 'Lab Stock'));
});
