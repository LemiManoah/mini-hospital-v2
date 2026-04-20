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

function finalizeCartForReversal(
    object $branch,
    object $user,
    object $pharmacyLocation,
    object $drug,
    string $cartNumber,
): PharmacyPosSale {
    $cart = PharmacyPosCart::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'user_id' => $user->id,
        'cart_number' => $cartNumber,
        'status' => PharmacyPosCartStatus::Active,
    ]);

    PharmacyPosCartItem::query()->create([
        'pharmacy_pos_cart_id' => $cart->id,
        'inventory_item_id' => $drug->id,
        'quantity' => 2,
        'unit_price' => 10.00,
        'discount_amount' => 0,
    ]);

    test()->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.carts.finalize', $cart), [
            'paid_amount' => '20.00',
            'payment_method' => 'cash',
        ]);

    return PharmacyPosSale::query()
        ->where('branch_id', $branch->id)
        ->where('status', PharmacyPosSaleStatus::Completed)
        ->latest()
        ->firstOrFail();
}

it('voids a completed sale and reverses stock', function (): void {
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

    $sale = finalizeCartForReversal($branch, $user, $pharmacyLocation, $drug, 'CART-P5-001');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.sales.void', $sale))
        ->assertRedirect(route('pharmacy.pos.sales.show', $sale));

    $sale->refresh();
    expect($sale->status)->toBe(PharmacyPosSaleStatus::Cancelled);

    $reversal = StockMovement::query()
        ->where('source_document_id', $sale->id)
        ->where('movement_type', StockMovementType::PosSaleReversal)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and((float) $reversal->quantity)->toBeGreaterThan(0.0);
});

it('refunds a completed sale and reverses stock', function (): void {
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

    $sale = finalizeCartForReversal($branch, $user, $pharmacyLocation, $drug, 'CART-P5-002');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.sales.refund', $sale), [
            'payment_method' => 'cash',
            'refund_amount' => '20.00',
        ])
        ->assertRedirect(route('pharmacy.pos.sales.show', $sale));

    $sale->refresh();
    expect($sale->status)->toBe(PharmacyPosSaleStatus::Refunded);

    $reversal = StockMovement::query()
        ->where('source_document_id', $sale->id)
        ->where('movement_type', StockMovementType::PosSaleReversal)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and((float) $reversal->quantity)->toBeGreaterThan(0.0);

    $refundPayment = $sale->payments()->where('is_refund', true)->first();
    expect($refundPayment)->not->toBeNull()
        ->and((float) $refundPayment->amount)->toBe(20.0);
});

it('rejects voiding a non-completed sale', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    $cancelledSale = PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-P5-CANCEL-001',
        'sale_type' => 'walk_in',
        'gross_amount' => 20,
        'discount_amount' => 0,
        'paid_amount' => 20,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Cancelled,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.sales.void', $cancelledSale))
        ->assertSessionHasErrors('sale');
});

it('rejects refunding a non-completed sale', function (): void {
    [
        $branch,
        ,
        $user,
        ,
        $pharmacyLocation,
    ] = createPharmacyModuleContext();

    $refundedSale = PharmacyPosSale::query()->create([
        'tenant_id' => $branch->tenant_id,
        'branch_id' => $branch->id,
        'inventory_location_id' => $pharmacyLocation->id,
        'sale_number' => 'POS-P5-REFUND-001',
        'sale_type' => 'walk_in',
        'gross_amount' => 20,
        'discount_amount' => 0,
        'paid_amount' => 20,
        'balance_amount' => 0,
        'change_amount' => 0,
        'status' => PharmacyPosSaleStatus::Refunded,
        'sold_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->post(route('pharmacy.pos.sales.refund', $refundedSale), [
            'payment_method' => 'cash',
            'refund_amount' => '20.00',
        ])
        ->assertSessionHasErrors('sale');
});

it('shows void and refund buttons on sale show page for authorized user', function (): void {
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

    $sale = finalizeCartForReversal($branch, $user, $pharmacyLocation, $drug, 'CART-P5-003');

    $this->withSession(['active_branch_id' => $branch->id])
        ->actingAs($user)
        ->get(route('pharmacy.pos.sales.show', $sale))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('pharmacy/pos/sales/show')
            ->where('can.void', true)
            ->where('can.refund', true));
});
