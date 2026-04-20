<?php

declare(strict_types=1);

use App\Enums\PharmacyPosCartStatus;
use App\Enums\PharmacyPosSaleStatus;
use App\Enums\StockMovementType;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Models\PharmacyPosSale;
use App\Models\StockMovement;
use Database\Seeders\PermissionSeeder;

require_once __DIR__.'/Controllers/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('posts stock movements when a sale is finalized', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
        ,
        ,
        $drug,
        ,
        ,
        $batch,
    ] = createPharmacyModuleContext();

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P3-001',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 5,
        'unit_price' => 10.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '50.00',
            'payment_method' => 'cash',
        ]);

    $sale = PharmacyPosSale::query()
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosSaleStatus::Completed)
        ->first();

    expect($sale)->not->toBeNull();

    $movement = StockMovement::query()
        ->where('source_document_type', PharmacyPosSale::class)
        ->where('source_document_id', $sale->id)
        ->where('inventory_item_id', $drug->id)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->movement_type)->toBe(StockMovementType::PosSale)
        ->and((float) $movement->quantity)->toBe(-5.0)
        ->and($movement->inventory_batch_id)->toBe($batch->id);
});

it('creates sale item allocations when finalizing', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
        ,
        ,
        $drug,
    ] = createPharmacyModuleContext();

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P3-002',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 3,
        'unit_price' => 10.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '30.00',
            'payment_method' => 'cash',
        ]);

    $sale = PharmacyPosSale::query()
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosSaleStatus::Completed)
        ->first();

    $saleItem = $sale->items()->first();

    expect($saleItem->allocations()->count())->toBeGreaterThanOrEqual(1);

    $allocation = $saleItem->allocations()->first();
    expect((float) $allocation->quantity)->toBe(3.0)
        ->and($allocation->batch_number_snapshot)->not->toBeNull()
        ->and($allocation->unit_cost_snapshot)->not->toBeNull();
});

it('reduces stock balance after a finalized sale', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
        ,
        ,
        $drug,
    ] = createPharmacyModuleContext();

    $stockBefore = (float) StockMovement::query()
        ->where('inventory_item_id', $drug->id)
        ->where('inventory_location_id', $pharmacyLocation->id)
        ->sum('quantity');

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P3-003',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 10,
        'unit_price' => 10.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '100.00',
            'payment_method' => 'cash',
        ]);

    $stockAfter = (float) StockMovement::query()
        ->where('inventory_item_id', $drug->id)
        ->where('inventory_location_id', $pharmacyLocation->id)
        ->sum('quantity');

    expect($stockAfter)->toBe($stockBefore - 10.0);
});

it('rejects a sale when stock is insufficient', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
        ,
        ,
        $drug,
    ] = createPharmacyModuleContext();

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P3-004',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 999,
        'unit_price' => 10.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '9990.00',
            'payment_method' => 'cash',
        ])
        ->assertSessionHasErrors('cart');

    expect(PharmacyPosSale::query()->where('branch_id', $branch->id)->count())->toBe(0);
    expect(StockMovement::query()->where('movement_type', StockMovementType::PosSale)->count())->toBe(0);
});

it('uses FEFO ordering when allocating from multiple batches', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
        ,
        ,
        ,
        $partialDrug,
        ,
        ,
        $partialBatch,
    ] = createPharmacyModuleContext();

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P3-005',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $partialDrug->id,
        'quantity' => 2,
        'unit_price' => 8.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '16.00',
            'payment_method' => 'cash',
        ]);

    $sale = PharmacyPosSale::query()
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosSaleStatus::Completed)
        ->first();

    expect($sale)->not->toBeNull();

    $allocation = $sale->items()->first()->allocations()->first();
    expect($allocation->inventory_batch_id)->toBe($partialBatch->id);
});
