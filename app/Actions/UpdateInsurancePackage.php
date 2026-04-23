<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\UpdateInsurancePackageDTO;
use App\Models\InsurancePackage;

final class UpdateInsurancePackage
{
    public function handle(InsurancePackage $insurancePackage, UpdateInsurancePackageDTO $attributes): InsurancePackage
    {
        $insurancePackage->update($attributes->toAttributes());

        return $insurancePackage;
    }
}
