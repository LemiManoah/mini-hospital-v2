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
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\SubscriptionPackage;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

function createPostGoodsReceiptContext(): array
{
    static $sequence = 1;

    $country = Country::query()->create([
        'country_name' => 'Post GR Country '.$sequence,
        'country_code' => 'PGR'.$sequence,
        'dial_code' => '+256',
        'currency' => 'UGX',
        'currency_symbol' => 'USh',
    ]);

    $package = SubscriptionPackage::query()->create([
        'name' => 'Post GR Package '.$sequence,
        'users' => 10 + $sequence,
        'price' => 1000,
        'status' => GeneralStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Post GR Tenant '.$sequence,
        'domain' => 'post-gr-'.$sequence.'.test',
        'has_branches' => true,
        'subscription_package_id' => $package->id,
        'status' => GeneralStatus::ACTIVE,
        'facility_level' => FacilityLevel::HOSPITAL,
        'country_id' => $country->id,
        'onboarding_completed_at' => now(),
        'onboarding_current_step' => 'completed',
    ]);

    $currency = Currency::query()->create([
        'code' => 'PGR'.$sequence,
        'name' => 'Post GR Currency '.$sequence,
        'symbol' => 'USh',
    ]);

    $branch = FacilityBranch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Post GR Branch '.$sequence,
        'branch_code' => 'PGB'.$sequence,
        'currency_id' => $currency->id,
        'status' => GeneralStatus::ACTIVE,
        'is_main_branch' => true,
        'has_store' => true,
    ]);

    $user = User::query()->create([
        'tenant_id' => $tenant->id,
        'email' => 'post.gr.user'.$sequence.'@test.com',
        'password' => Hash::make('password'),
        'is_support' => false,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Post GR Supplier '.$sequence,
        'is_active' => true,
    ]);

    $item = InventoryItem::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Post GR Item '.$sequence,
        'item_type' => InventoryItemType::DRUG,
        'is_active' => true,
    ]);

    $location = InventoryLocation::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Post GR Store '.$sequence,
        'location_code' => 'PGL'.$sequence,
        'type' => InventoryLocationType::MAIN_STORE,
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'order_number' => 'PGR-PO-'.$sequence,
        'status' => PurchaseOrderStatus::Approved,
        'order_date' => now(),
        'total_amount' => 2500,
    ]);

    $purchaseOrderItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_item_id' => $item->id,
        'quantity_ordered' => 50,
        'unit_cost' => 50,
        'total_cost' => 2500,
    ]);

    $goodsReceipt = GoodsReceipt::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'purchase_order_id' => $purchaseOrder->id,
        'inventory_location_id' => $location->id,
        'receipt_number' => 'PGR-GR-'.$sequence,
        'status' => GoodsReceiptStatus::Draft,
        'receipt_date' => now(),
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'inventory_item_id' => $item->id,
        'quantity_received' => 10,
        'unit_cost' => 50,
    ]);

    $sequence++;

    return [$user, $goodsReceipt];
}

it('rejects posting when the goods receipt is no longer draft in storage', function (): void {
    [$user, $goodsReceipt] = createPostGoodsReceiptContext();

    $this->actingAs($user);

    $staleGoodsReceipt = $goodsReceipt->fresh();

    GoodsReceipt::query()
        ->whereKey($goodsReceipt->id)
        ->update([
            'status' => GoodsReceiptStatus::Posted,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

    $action = resolve(PostGoodsReceipt::class);

    expect(fn () => $action->handle($staleGoodsReceipt))->toThrow(
        fn (HttpException $exception): bool => $exception->getStatusCode() === 422
            && $exception->getMessage() === 'Only draft goods receipts can be posted.',
    );
});

it('creates inventory batches and stock movements when posting a goods receipt', function (): void {
    [$user, $goodsReceipt] = createPostGoodsReceiptContext();

    $this->actingAs($user);

    $action = resolve(PostGoodsReceipt::class);
    $postedGoodsReceipt = $action->handle($goodsReceipt);

    $postedGoodsReceiptItem = $postedGoodsReceipt->items->firstOrFail();

    $batch = InventoryBatch::query()
        ->where('goods_receipt_item_id', $postedGoodsReceiptItem->id)
        ->first();

    expect($batch)->not->toBeNull()
        ->and((string) $batch?->inventory_location_id)->toBe((string) $postedGoodsReceipt->inventory_location_id)
        ->and((float) $batch?->quantity_received)->toBe(10.0);

    $movement = StockMovement::query()
        ->where('source_line_id', $postedGoodsReceiptItem->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and((string) $movement?->inventory_batch_id)->toBe((string) $batch?->id)
        ->and($movement?->movement_type?->value)->toBe('receipt')
        ->and((float) $movement?->quantity)->toBe(10.0);
});
