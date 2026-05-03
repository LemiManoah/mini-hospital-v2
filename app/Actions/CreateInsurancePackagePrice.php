<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePackage;
use App\Models\InsurancePackagePrice;
use Illuminate\Support\Facades\Auth;

final readonly class CreateInsurancePackagePrice
{
    public function handle(InsurancePackage $package, array $data): InsurancePackagePrice
    {
        /** @var array{
         *   facility_branch_id: string,
         *   billable_type: string,
         *   billable_id: string,
         *   price: numeric-string,
         *   effective_from: string,
         *   effective_to: string|null,
         *   status: string
         * } $data
         */
        return InsurancePackagePrice::query()->create([
            'insurance_package_id' => $package->id,
            'facility_branch_id' => $data['facility_branch_id'],
            'billable_type' => $data['billable_type'],
            'billable_id' => $data['billable_id'],
            'price' => $data['price'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'status' => $data['status'],
            'created_by' => Auth::id(),
        ]);
    }
}
