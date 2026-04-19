<?php

declare(strict_types=1);

use App\Enums\PharmacyPosCartStatus;
use App\Enums\PharmacyPosSaleStatus;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Models\PharmacyPosSale;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

require_once __DIR__.'/Controllers/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('renders checkout page with cart data', function (): void {
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
        'cart_number' => 'CART-P2-001',
        'status' => PharmacyPosCartStatus::Active,
        'customer_name' => 'Jane Doe',
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
        ->get(route('pharmacy.pos.carts.checkout', $cart))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/checkout')
            ->where('cart.cart_number', 'CART-P2-001')
            ->where('cart.customer_name', 'Jane Doe')
            ->where('cart.total_amount', 30)
            ->has('cart.items', 1));
});

it('rejects checkout on a non-active cart', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P2-002',
        'status' => PharmacyPosCartStatus::Converted,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.carts.checkout', $cart))
        ->assertNotFound();
});

it('finalizes a sale and redirects to the sale show page', function (): void {
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
        'cart_number' => 'CART-P2-003',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 2,
        'unit_price' => 15.00,
        'discount_amount' => 0,
    ]);

    $response = $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '30.00',
            'payment_method' => 'cash',
            'reference_number' => '',
            'notes' => '',
        ]);

    $sale = PharmacyPosSale::query()
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosSaleStatus::Completed)
        ->first();

    expect($sale)->not->toBeNull();

    $response->assertRedirect(route('pharmacy.pos.sales.show', $sale));

    expect((float) $sale->gross_amount)->toBe(30.0)
        ->and((float) $sale->paid_amount)->toBe(30.0)
        ->and((float) $sale->balance_amount)->toBe(0.0)
        ->and($sale->items()->count())->toBe(1)
        ->and($sale->payments()->count())->toBe(1);

    $cart->refresh();
    expect($cart->status)->toBe(PharmacyPosCartStatus::Converted);
});

it('rejects finalizing a cart with no items', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P2-004',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '10.00',
            'payment_method' => 'cash',
        ])
        ->assertSessionHasErrors('cart');
});

it('renders the sale show page', function (): void {
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
        'cart_number' => 'CART-P2-005',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 1,
        'unit_price' => 10.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '10.00',
            'payment_method' => 'cash',
        ]);

    $sale = PharmacyPosSale::query()
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosSaleStatus::Completed)
        ->first();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.sales.show', $sale))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/sales/show')
            ->where('sale.status', 'completed')
            ->has('sale.items', 1)
            ->has('sale.payments', 1));
});

it('records an additional payment on a completed sale', function (): void {
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
        'cart_number' => 'CART-P2-006',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 2,
        'unit_price' => 20.00,
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
        ->latest()
        ->first();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.sales.payments.store', $sale), [
            'amount' => '10.00',
            'payment_method' => 'mobile_money',
            'reference_number' => 'TXN-12345',
        ])
        ->assertRedirect();

    $sale->refresh()->load('payments');

    expect($sale->payments)->toHaveCount(2)
        ->and((float) $sale->paid_amount)->toBe(40.0)
        ->and((float) $sale->balance_amount)->toBe(0.0);
});
