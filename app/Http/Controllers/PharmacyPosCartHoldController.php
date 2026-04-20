<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\HoldPharmacyPosCartAction;
use App\Actions\ResumePharmacyPosCartAction;
use App\Models\PharmacyPosCart;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

final readonly class PharmacyPosCartHoldController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.create'),
        ];
    }

    public function store(PharmacyPosCart $cart, HoldPharmacyPosCartAction $action): RedirectResponse
    {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);

        $action->handle($cart);

        return to_route('pharmacy.pos.index')->with('success', 'Cart held.');
    }

    public function destroy(PharmacyPosCart $cart, ResumePharmacyPosCartAction $action): RedirectResponse
    {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);

        $action->handle($cart);

        return to_route('pharmacy.pos.index')->with('success', 'Cart resumed.');
    }
}
