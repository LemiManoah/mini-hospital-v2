<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosCartStatus;
use App\Models\PharmacyPosCart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final readonly class ResumePharmacyPosCartAction
{
    public function handle(PharmacyPosCart $cart): PharmacyPosCart
    {
        if ($cart->status !== PharmacyPosCartStatus::Held) {
            throw ValidationException::withMessages([
                'cart' => 'Only held carts can be resumed.',
            ]);
        }

        $existingActive = PharmacyPosCart::query()
            ->where('branch_id', $cart->branch_id)
            ->where('user_id', Auth::id())
            ->where('status', PharmacyPosCartStatus::Active)
            ->exists();

        if ($existingActive) {
            throw ValidationException::withMessages([
                'cart' => 'You already have an active cart. Complete or hold it before resuming another.',
            ]);
        }

        $cart->update([
            'status' => PharmacyPosCartStatus::Active,
            'held_at' => null,
        ]);

        return $cart->refresh();
    }
}
