<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PharmacyPosCart;
use Illuminate\Support\Str;

final class PharmacyPosCartNumberGenerator
{
    public function generate(?string $tenantId): string
    {
        do {
            $cartNumber = 'CART-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && PharmacyPosCart::query()
                ->where('cart_number', $cartNumber)
                ->exists()
        );

        return $cartNumber;
    }
}
