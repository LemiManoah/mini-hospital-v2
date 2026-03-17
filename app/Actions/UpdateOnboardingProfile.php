<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Address;
use App\Models\Tenant;

final class UpdateOnboardingProfile
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Tenant $tenant, array $data): Tenant
    {
        $address = isset($data['address_id'])
            ? Address::query()->find($data['address_id'])
            : $tenant->address;

        $tenant->update([
            'name' => $data['name'],
            'domain' => $data['domain'] ?: null,
            'facility_level' => $data['facility_level'],
            'country_id' => $address?->country_id ?? ($data['country_id'] ?: null),
            'address_id' => $address?->id,
            'updated_by' => auth()->id(),
            'onboarding_current_step' => 'branch',
        ]);

        return $tenant->fresh();
    }
}
