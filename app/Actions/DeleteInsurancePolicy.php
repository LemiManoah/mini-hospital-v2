<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePolicy;

final readonly class DeleteInsurancePolicy
{
    public function handle(InsurancePolicy $policy): bool
    {
        return $policy->delete() === true;
    }
}
