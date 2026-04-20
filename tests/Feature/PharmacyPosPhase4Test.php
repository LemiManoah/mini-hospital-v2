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

it('holds an active cart', function (): void {
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
        'cart_number' => 'CART-P4-001',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.hold', $cart))
        ->assertRedirect(route('pharmacy.pos.index'));

    $cart->refresh();
    expect($cart->status)->toBe(PharmacyPosCartStatus::Held)
        ->and($cart->held_at)->not->toBeNull();
});

it('resumes a held cart', function (): void {
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
        'cart_number' => 'CART-P4-002',
        'status' => PharmacyPosCartStatus::Held,
        'held_at' => now()->subMinutes(5),
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('pharmacy.pos.carts.resume', $cart))
        ->assertRedirect(route('pharmacy.pos.index'));

    $cart->refresh();
    expect($cart->status)->toBe(PharmacyPosCartStatus::Active)
        ->and($cart->held_at)->toBeNull();
});

it('rejects resuming a held cart when user already has an active cart', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P4-003',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    $heldCart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => 'CART-P4-004',
        'status' => PharmacyPosCartStatus::Held,
        'held_at' => now()->subMinutes(3),
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('pharmacy.pos.carts.resume', $heldCart))
        ->assertSessionHasErrors('cart');
});

it('renders the sales history page', function (): void {
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
        'cart_number' => 'CART-P4-005',
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

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.history'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/history')
            ->has('sales.data', 1)
            ->has('filters')
            ->has('statuses'));
});

it('filters sales history by search term', function (): void {
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

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-SEARCH-001',
        'sale_type' => 'walk_in',
        'customer_name' => 'Alice Smith',
        'gross_amount' => 50,
        'discount_amount' => 0,
        'paid_amount' => 50,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-SEARCH-002',
        'sale_type' => 'walk_in',
        'customer_name' => 'Bob Jones',
        'gross_amount' => 30,
        'discount_amount' => 0,
        'paid_amount' => 30,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.history', ['search' => 'Alice']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/history')
            ->has('sales.data', 1)
            ->where('sales.data.0.customer_name', 'Alice Smith'));
});

it('filters sales history by status', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-STATUS-001',
        'sale_type' => 'walk_in',
        'customer_name' => 'Completed Customer',
        'gross_amount' => 50,
        'discount_amount' => 0,
        'paid_amount' => 50,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now()->subDay(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-STATUS-002',
        'sale_type' => 'walk_in',
        'customer_name' => 'Voided Customer',
        'gross_amount' => 30,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'balance_amount' => 30,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Voided,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.history', ['status' => PharmacyPosSaleStatus::Completed->value]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/history')
            ->has('sales.data', 1)
            ->where('sales.data.0.customer_name', 'Completed Customer')
            ->where('filters.status', PharmacyPosSaleStatus::Completed->value));
});

it('filters sales history by from date', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-FROM-001',
        'sale_type' => 'walk_in',
        'customer_name' => 'Older Sale',
        'gross_amount' => 40,
        'discount_amount' => 0,
        'paid_amount' => 40,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now()->subDays(5),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-FROM-002',
        'sale_type' => 'walk_in',
        'customer_name' => 'Recent Sale',
        'gross_amount' => 60,
        'discount_amount' => 0,
        'paid_amount' => 60,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now()->subDay(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $from = now()->subDays(2)->toDateString();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.history', ['from' => $from]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/history')
            ->has('sales.data', 1)
            ->where('sales.data.0.customer_name', 'Recent Sale')
            ->where('filters.from', $from));
});

it('filters sales history by to date', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-TO-001',
        'sale_type' => 'walk_in',
        'customer_name' => 'Included Sale',
        'gross_amount' => 40,
        'discount_amount' => 0,
        'paid_amount' => 40,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now()->subDays(3),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-TO-002',
        'sale_type' => 'walk_in',
        'customer_name' => 'Excluded Sale',
        'gross_amount' => 80,
        'discount_amount' => 0,
        'paid_amount' => 80,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Completed,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $to = now()->subDays(2)->toDateString();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.history', ['to' => $to]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/history')
            ->has('sales.data', 1)
            ->where('sales.data.0.customer_name', 'Included Sale')
            ->where('filters.to', $to));
});
