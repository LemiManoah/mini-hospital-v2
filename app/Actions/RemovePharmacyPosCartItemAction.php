<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PharmacyPosCartItem;

final readonly class RemovePharmacyPosCartItemAction
{
    public function handle(PharmacyPosCartItem $cartItem): void
    {
        $cartItem->allocations()->delete();
        $cartItem->delete();
    }
}
