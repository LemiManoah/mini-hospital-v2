<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePolicy;
use App\Models\InsurancePolicyItem;
use Illuminate\Support\Facades\Auth;

final readonly class CreateInsurancePolicyItem
{
    /**
     * @param  array{
     *     item_id: string,
     *     price: numeric-string,
     *     copay_type: string,
     *     copay_value: numeric-string,
     *     effective_from?: string|null,
     *     effective_to?: string|null,
     *     status: string
     * }  $data
     */
    public function handle(InsurancePolicy $policy, array $data): InsurancePolicyItem
    {
        return $policy->items()->create([
            'tenant_id' => $policy->tenant_id,
            'item_type' => $policy->policy_type->itemType()->value,
            'item_id' => $data['item_id'],
            'price' => $data['price'],
            'copay_type' => $data['copay_type'],
            'copay_value' => $data['copay_value'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'status' => $data['status'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
