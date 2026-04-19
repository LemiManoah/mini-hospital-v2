<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PharmacyPosSale;
use Illuminate\Support\Str;

final class PharmacyPosSaleNumberGenerator
{
    public function generate(?string $tenantId): string
    {
        do {
            $saleNumber = 'POS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && PharmacyPosSale::query()
                ->where('sale_number', $saleNumber)
                ->exists()
        );

        return $saleNumber;
    }
}
