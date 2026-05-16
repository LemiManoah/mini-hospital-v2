<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePackage;
use App\Models\InsurancePolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateInsurancePolicy
{
    /**
     * @param  array{
     *     facility_branch_id: string,
     *     name: string,
     *     policy_type: string,
     *     effective_from?: string|null,
     *     effective_to?: string|null,
     *     status: string,
     *     items?: list<array{charge_master_id: string, price: numeric-string, copay_type: string, copay_value: numeric-string, effective_from?: string|null, effective_to?: string|null, status: string}>
     * }  $data
     */
    public function handle(InsurancePackage $insurancePackage, array $data): InsurancePolicy
    {
        return DB::transaction(function () use ($insurancePackage, $data): InsurancePolicy {
            $policy = InsurancePolicy::query()->create([
                'tenant_id' => $insurancePackage->tenant_id,
                'insurance_package_id' => $insurancePackage->id,
                'facility_branch_id' => $data['facility_branch_id'],
                'name' => $data['name'],
                'policy_type' => $data['policy_type'],
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to' => $data['effective_to'] ?? null,
                'status' => $data['status'],
                'created_by' => Auth::id(),
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $policy->items()->create([
                    'tenant_id' => $policy->tenant_id,
                    'charge_master_id' => $item['charge_master_id'],
                    'price' => $item['price'],
                    'copay_type' => $item['copay_type'],
                    'copay_value' => $item['copay_value'],
                    'effective_from' => $item['effective_from'] ?? $policy->effective_from?->toDateString(),
                    'effective_to' => $item['effective_to'] ?? $policy->effective_to?->toDateString(),
                    'status' => $item['status'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            return $policy->refresh();
        });
    }
}
