<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\UpdateInsurancePackageDTO;
use App\Models\InsurancePackage;
use Illuminate\Support\Facades\Auth;

final class UpdateInsurancePackage
{
    public function handle(InsurancePackage $insurancePackage, UpdateInsurancePackageDTO $attributes): InsurancePackage
    {
        $insurancePackage->update([
            ...$attributes->toAttributes(),
            'updated_by' => Auth::id(),
        ]);

        return $insurancePackage;
    }
}
