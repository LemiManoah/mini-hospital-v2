<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Patient\CreateInsurancePackageDTO;
use App\Models\InsurancePackage;
use Illuminate\Support\Facades\Auth;

final readonly class CreateInsurancePackage
{
    public function handle(CreateInsurancePackageDTO $data): InsurancePackage
    {
        return InsurancePackage::query()->create([
            ...$data->toAttributes(),
            'created_by' => Auth::id(),
        ]);
    }
}
