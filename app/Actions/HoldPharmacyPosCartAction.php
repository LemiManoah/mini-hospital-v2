<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosCartStatus;
use App\Models\PharmacyPosCart;
use Illuminate\Validation\ValidationException;

final readonly class HoldPharmacyPosCartAction
{
    public function handle(PharmacyPosCart $cart): PharmacyPosCart
    {
        if ($cart->status !== PharmacyPosCartStatus::Active) {
            throw ValidationException::withMessages([
                'cart' => 'Only active carts can be held.',
            ]);
        }

        $cart->update([
            'status' => PharmacyPosCartStatus::Held,
            'held_at' => now(),
        ]);

        return $cart->refresh();
    }
}
