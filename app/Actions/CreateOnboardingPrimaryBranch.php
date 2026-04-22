<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Onboarding\CreateOnboardingPrimaryBranchDTO;
use App\Enums\GeneralStatus;
use App\Models\Address;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;

final class CreateOnboardingPrimaryBranch
{
    public function handle(Tenant $tenant, User $user, CreateOnboardingPrimaryBranchDTO $data): FacilityBranch
    {
        $address = Address::query()->findOrFail($data->addressId);

        $branch = FacilityBranch::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'is_main_branch' => true,
            ],
            [
                'name' => $data->name,
                'branch_code' => $data->branchCode,
                'address_id' => $address->id,
                'main_contact' => $data->mainContact,
                'other_contact' => $data->otherContact,
                'email' => $data->email,
                'currency_id' => $data->currencyId,
                'status' => GeneralStatus::ACTIVE,
                'has_store' => $data->hasStore,
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
            'country_id' => $address->country_id ?? $tenant->country_id,
            'updated_by' => $user->id,
            'onboarding_current_step' => 'departments',
        ]);

        BranchContext::setActiveBranchId($branch->id);

        return $branch;
    }
}
