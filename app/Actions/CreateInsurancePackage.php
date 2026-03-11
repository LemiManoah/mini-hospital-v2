<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePackage;

final readonly class CreateInsurancePackage
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): InsurancePackage
    {
        return InsurancePackage::query()->create($data);
    }
}
