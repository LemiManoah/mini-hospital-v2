<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FacilityBranch;
use App\Models\User;
use App\Support\BranchContext;

final class FacilityBranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null
            && $user->hasPermissionTo('facility_branches.view');
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null
            && $user->hasPermissionTo('facility_branches.create');
    }

    public function view(User $user, FacilityBranch $facilityBranch): bool
    {
        return $this->belongsToUsersTenant($user, $facilityBranch)
            && $user->hasPermissionTo('facility_branches.view');
    }

    public function update(User $user, FacilityBranch $facilityBranch): bool
    {
        return $this->belongsToUsersTenant($user, $facilityBranch)
            && $user->hasPermissionTo('facility_branches.update');
    }

    public function delete(User $user, FacilityBranch $facilityBranch): bool
    {
        return $this->belongsToUsersTenant($user, $facilityBranch)
            && $user->hasPermissionTo('facility_branches.delete');
    }

    public function switchTo(User $user, FacilityBranch $facilityBranch): bool
    {
        return $this->belongsToUsersTenant($user, $facilityBranch)
            && $user->hasPermissionTo('facility_branches.update')
            && BranchContext::canAccessBranch($user, $facilityBranch->id);
    }

    private function belongsToUsersTenant(User $user, FacilityBranch $facilityBranch): bool
    {
        return $user->tenant_id !== null
            && $facilityBranch->tenant_id === $user->tenant_id;
    }
}
