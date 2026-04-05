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
use App\Support\InventoryStockLedger;

function createInventoryStockLedgerContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Ledger Country '.$sequence,
        'country_code' => 'LD'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Ledger Package '.$sequence,
        'users' => 15 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Ledger Tenant '.$sequence,
        'domain' => 'ledger-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'LD'.$sequence,
        'name' => 'Ledger Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Ledger Branch '.$sequence,
        'branch_code' => 'LDB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Ledger Supplier '.$sequence,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Ledger Store '.$sequence,
        'location_code' => 'LDS'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Ledger Item '.$sequence,
        'item_type' => InventoryItemType::SUPPLY,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'LEDGER-PO-'.$sequence,
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
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'LEDGER-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 10,
        'unit_cost' => 50,
        'batch_number' => 'BATCH-'.$sequence,
        'expiry_date' => now()->addMonth(),
    ]);

    $sequence++;

    resolve(PostGoodsReceipt::class)->handle($goodsReceipt);

    return [$branch, $location, $item];
}

it('summarizes stock balances by location and batch from posted movements', function (): void {
    [$branch, $location, $item] = createInventoryStockLedgerContext();

    $ledger = resolve(InventoryStockLedger::class);

    $locationBalances = $ledger->summarizeByLocation($branch->id);
    $batchBalances = $ledger->summarizeByBatch($branch->id);

    expect($locationBalances)->toHaveCount(1)
        ->and($locationBalances->first())->toMatchArray([
            'inventory_location_id' => $location->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10.0,
        ]);

    expect($batchBalances)->toHaveCount(1)
        ->and($batchBalances->first()['inventory_location_id'])->toBe($location->id)
        ->and($batchBalances->first()['inventory_item_id'])->toBe($item->id)
        ->and($batchBalances->first()['batch_number'])->toBe('BATCH-1')
        ->and($batchBalances->first()['quantity'])->toBe(10.0);
});
