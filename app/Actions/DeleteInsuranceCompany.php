<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsuranceCompany;

final class DeleteInsuranceCompany
{
    public function handle(InsuranceCompany $insuranceCompany): bool
    {
        return $insuranceCompany->delete();
    }
}
