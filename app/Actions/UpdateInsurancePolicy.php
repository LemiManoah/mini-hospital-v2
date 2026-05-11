<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InsurancePolicy;
use Illuminate\Support\Facades\Auth;

final readonly class UpdateInsurancePolicy
{
    /**
     * @param  array{
     *     name: string,
     *     effective_from?: string|null,
     *     effective_to?: string|null,
     *     status: string
     * }  $data
     */
    public function handle(InsurancePolicy $policy, array $data): InsurancePolicy
    {
        $policy->update([
            'name' => $data['name'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'status' => $data['status'],
            'updated_by' => Auth::id(),
        ]);

        return $policy->refresh();
    }
}
