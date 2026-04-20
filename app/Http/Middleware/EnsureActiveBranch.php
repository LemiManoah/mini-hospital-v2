<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveBranch
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (
            $request->routeIs('facility-manager.*')
            || $request->routeIs('branch-switcher.*')
            || $request->routeIs('facility-branches.*')
        ) {
            return $next($request);
        }

        if ($user->tenant_id === null) {
            BranchContext::clear();

            return $next($request);
        }

        $activeBranchId = BranchContext::getActiveBranchId();

        if ($activeBranchId !== null && BranchContext::canAccessBranch($user, $activeBranchId)) {
            return $next($request);
        }

        $accessibleBranches = BranchContext::getAccessibleBranches($user);

        if ($accessibleBranches->isEmpty()) {
            BranchContext::clear();

            return $next($request);
        }

        $fallbackBranchId = (string) $accessibleBranches->first()->id;
        BranchContext::setActiveBranchId($fallbackBranchId);

        if ($accessibleBranches->count() === 1) {
            return $next($request);
        }

        return to_route('branch-switcher.index');
    }
}
