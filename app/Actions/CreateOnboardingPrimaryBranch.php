<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GeneralStatus;
use App\Models\Address;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;

final class CreateOnboardingPrimaryBranch
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Tenant $tenant, User $user, array $data): FacilityBranch
    {
        $address = Address::query()->create([
            'city' => $data['city'],
            'district' => $data['district'] ?: null,
            'state' => $data['state'] ?: null,
            'country_id' => $data['country_id'] ?: $tenant->country_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $branch = FacilityBranch::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'is_main_branch' => true,
            ],
            [
                'name' => $data['name'],
                'branch_code' => $data['branch_code'],
                'address_id' => $address->id,
                'main_contact' => $data['main_contact'] ?: null,
                'other_contact' => $data['other_contact'] ?: null,
                'email' => $data['email'] ?: null,
                'currency_id' => $data['currency_id'],
                'status' => GeneralStatus::ACTIVE,
                'has_store' => (bool) ($data['has_store'] ?? false),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
        );

        if ($user->staff !== null) {
            $branch->staff()->syncWithoutDetaching([
                $user->staff->id => ['is_primary_location' => true],
            ]);
        }

        $tenant->update([
            'has_branches' => true,
            'updated_by' => $user->id,
            'onboarding_current_step' => 'departments',
        ]);

        BranchContext::setActiveBranchId($branch->id);

        return $branch;
    }
}
