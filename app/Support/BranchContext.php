<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\GeneralStatus;
use App\Models\FacilityBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class BranchContext
{
    public const string SESSION_KEY = 'active_branch_id';

    public static function getActiveBranchId(): ?string
    {
        $branchId = Session::get(self::SESSION_KEY);

        return is_string($branchId) && $branchId !== '' ? $branchId : null;
    }

    public static function setActiveBranchId(string $branchId): void
    {
        Session::put(self::SESSION_KEY, $branchId);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public static function getActiveBranch(?User $user = null): ?FacilityBranch
    {
        if (! $user instanceof User) {
            $authenticatedUser = Auth::user();
            if (! $authenticatedUser instanceof User) {
                return null;
            }

            $user = $authenticatedUser;
        }

        $tenantId = $user->tenantId();

        if ($tenantId === null) {
            return null;
        }

        $branchId = self::getActiveBranchId();

        if ($branchId === null) {
            return null;
        }

        return FacilityBranch::query()
            ->where('tenant_id', $tenantId)
            ->where('status', GeneralStatus::ACTIVE)
            ->find($branchId);
    }

    /**
     * @return Collection<int, FacilityBranch>
     */
    public static function getAccessibleBranches(User $user): Collection
    {
        $tenantId = $user->tenantId();

        if ($tenantId === null) {
            return new Collection;
        }

        $baseQuery = FacilityBranch::query()
            ->where('tenant_id', $tenantId)
            ->where('status', GeneralStatus::ACTIVE)
            ->orderByDesc('is_main_branch')
            ->orderBy('name');

        if ($user->isSupportUser()) {
            return $baseQuery->get();
        }

        $staffId = $user->staffId();

        if ($staffId !== null) {
            return $baseQuery
                ->whereHas('staff', function (Builder $query) use ($staffId): void {
                    $query->where('staff.id', $staffId);
                })
                ->get();
        }

        return $baseQuery->get();
    }

    public static function canAccessBranch(User $user, string $branchId): bool
    {
        $tenantId = $user->tenantId();

        if ($tenantId === null) {
            return false;
        }

        $query = FacilityBranch::query()
            ->whereKey($branchId)
            ->where('tenant_id', $tenantId)
            ->where('status', GeneralStatus::ACTIVE);

        if ($user->isSupportUser()) {
            return $query->exists();
        }

        $staffId = $user->staffId();

        if ($staffId !== null) {
            return $query->whereHas('staff', function (Builder $staffQuery) use ($staffId): void {
                $staffQuery->where('staff.id', $staffId);
            })->exists();
        }

        return $query->exists();
    }
}
