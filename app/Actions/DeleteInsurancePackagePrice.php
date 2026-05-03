<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePackagePrice;

final class DeleteInsurancePackagePrice
{
    public function handle(InsurancePackagePrice $price): bool
    {
        return $price->delete() === true;
    }
}
