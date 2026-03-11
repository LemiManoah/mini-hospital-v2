<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsuranceCompany;

final readonly class CreateInsuranceCompany
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): InsuranceCompany
    {
        return InsuranceCompany::query()->create($data);
    }
}
