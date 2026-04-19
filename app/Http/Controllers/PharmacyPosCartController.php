<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddItemToPharmacyPosCartAction;
use App\Actions\RemovePharmacyPosCartItemAction;
use App\Actions\UpdatePharmacyPosCartItemAction;
use App\Enums\PharmacyPosCartStatus;
use App\Http\Requests\StorePharmacyPosCartItemRequest;
use App\Http\Requests\UpdatePharmacyPosCartItemRequest;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class PharmacyPosCartController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.create'),
        ];
    }

    public function store(
        StorePharmacyPosCartItemRequest $request,
        PharmacyPosCart $cart,
        AddItemToPharmacyPosCartAction $action,
    ): RedirectResponse {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 403, 'This cart is no longer active.');

        $action->handle($cart, $request->validated());

        return back()->with('success', 'Item added to cart.');
    }

    public function update(
        UpdatePharmacyPosCartItemRequest $request,
        PharmacyPosCart $cart,
        PharmacyPosCartItem $item,
        UpdatePharmacyPosCartItemAction $action,
    ): RedirectResponse {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($item->pharmacy_pos_cart_id === $cart->id, 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 403, 'This cart is no longer active.');

        $action->handle($item, $request->validated());

        return back()->with('success', 'Cart item updated.');
    }

    public function destroy(
        PharmacyPosCart $cart,
        PharmacyPosCartItem $item,
        RemovePharmacyPosCartItemAction $action,
    ): RedirectResponse {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($item->pharmacy_pos_cart_id === $cart->id, 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 403, 'This cart is no longer active.');

        $action->handle($item);

        return back()->with('success', 'Item removed from cart.');
    }
}
