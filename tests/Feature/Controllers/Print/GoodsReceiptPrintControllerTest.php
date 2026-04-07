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

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createGoodsReceiptPrintContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'GR Print Country '.$sequence,
        'country_code' => 'GP'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'GR Print Package '.$sequence,
        'users' => 20 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'GR Print Tenant '.$sequence,
        'domain' => 'gr-print-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'GP'.$sequence,
        'name' => 'GR Print Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Print Branch '.$sequence,
        'branch_code' => 'GPB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'gr.print.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Print Supplier '.$sequence,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'GR Print Item '.$sequence,
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $mainStore = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'GR Print Main Store '.$sequence,
        'location_code' => 'GP-MS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $po = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'GP-PO-'.$sequence,
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

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $po->id,
        'inventory_location_id' => $mainStore->id,
        'receipt_number' => 'GR-PRINT-'.$sequence,
        'status' => GoodsReceiptStatus::Posted,
        'receipt_date' => now(),
        'posted_at' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $poItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 25,
        'unit_cost' => 50,
        'batch_number' => 'BATCH-GP-'.$sequence,
        'expiry_date' => now()->addYear()->toDateString(),
    ]);

    $sequence++;

    return [$branch, $user, $goodsReceipt];
}

it('streams a pdf for a goods receipt', function (): void {
    [$branch, $user, $goodsReceipt] = createGoodsReceiptPrintContext();

    $user->assignRole('store_keeper');

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('goods-receipts.print', $goodsReceipt));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toContain('%PDF');
});

it('does not expose a main-store goods receipt through the pharmacy print route', function (): void {
    [$branch, $user, $goodsReceipt] = createGoodsReceiptPrintContext();

    $user->assignRole('store_keeper');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.receipts.print', $goodsReceipt))
        ->assertNotFound();
});
