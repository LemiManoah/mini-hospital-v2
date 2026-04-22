<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePackage;

final class DeleteInsurancePackage
{
    public function handle(InsurancePackage $insurancePackage): bool
    {
        return $insurancePackage->delete() === true;
    }
}
