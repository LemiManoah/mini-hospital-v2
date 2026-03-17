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
        $address = $tenant->address;

        if (($data['city'] ?? null) || ($data['district'] ?? null) || ($data['state'] ?? null) || ($data['country_id'] ?? null)) {
            if (! $address instanceof Address) {
                $address = Address::query()->create([
                    'city' => $data['city'] ?: 'Not set',
                    'district' => $data['district'] ?: null,
                    'state' => $data['state'] ?: null,
                    'country_id' => $data['country_id'] ?: null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            } else {
                $address->update([
                    'city' => $data['city'] ?: $address->city,
                    'district' => $data['district'] ?: null,
                    'state' => $data['state'] ?: null,
                    'country_id' => $data['country_id'] ?: null,
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        $tenant->update([
            'name' => $data['name'],
            'domain' => $data['domain'] ?: null,
            'facility_level' => $data['facility_level'],
            'country_id' => $data['country_id'] ?: null,
            'address_id' => $address?->id,
            'updated_by' => auth()->id(),
            'onboarding_current_step' => 'branch',
        ]);

        return $tenant->fresh();
    }
}
