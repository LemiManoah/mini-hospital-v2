<?php

declare(strict_types=1);

use App\Enums\PharmacyPosCartStatus;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

require_once __DIR__.'/Controllers/PharmacyTestHelpers.php';

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

it('renders the pharmacy POS index page with no active cart', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('pharmacy/pos/index')
            ->where('activeCart', null)
            ->has('dispensingLocations')
            ->has('searchableItems'));
});

it('opens a new POS cart for the authenticated user', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.store'), [
            'inventory_location_id' => $pharmacyLocation->id,
            'customer_name' => 'John Doe',
            'customer_phone' => '+256700000001',
        ])
        ->assertRedirect(route('pharmacy.pos.index'));

    $cart = PharmacyPosCart::query()
        ->where('user_id', $user->id)
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosCartStatus::Active)
        ->first();

    expect($cart)->not->toBeNull()
        ->and($cart->customer_name)->toBe('John Doe')
        ->and($cart->inventory_location_id)->toBe($pharmacyLocation->id);
});

it('adds an item to an active POS cart', function (): void {
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
        'cart_number' => 'CART-TEST-001',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.items.store', $cart), [
            'inventory_item_id' => $drug->id,
            'quantity' => '2',
            'unit_price' => '5.00',
            'discount_amount' => '0',
        ])
        ->assertRedirect();

    expect(
        PharmacyPosCartItem::query()
            ->where('pharmacy_pos_cart_id', $cart->id)
            ->where('inventory_item_id', $drug->id)
            ->exists()
    )->toBeTrue();
});

it('removes an item from an active POS cart', function (): void {
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
        'cart_number' => 'CART-TEST-002',
        'status' => PharmacyPosCartStatus::Active,
    ]);

    $item = PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 1,
        'unit_price' => 5.00,
        'discount_amount' => 0,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->delete(route('pharmacy.pos.carts.items.destroy', [$cart, $item]))
        ->assertRedirect();

    expect(
        PharmacyPosCartItem::query()->find($item->id)
    )->toBeNull();
});

it('rejects adding an item to a non-active cart', function (): void {
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
        'cart_number' => 'CART-TEST-003',
        'status' => PharmacyPosCartStatus::Converted,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.items.store', $cart), [
            'inventory_item_id' => $drug->id,
            'quantity' => '1',
            'unit_price' => '5.00',
            'discount_amount' => '0',
        ])
        ->assertForbidden();
});
