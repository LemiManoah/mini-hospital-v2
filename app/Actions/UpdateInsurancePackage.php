<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePackage;

final class UpdateInsurancePackage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(InsurancePackage $insurancePackage, array $attributes): InsurancePackage
    {
        $insurancePackage->update($attributes);

        return $insurancePackage;
    }
}
