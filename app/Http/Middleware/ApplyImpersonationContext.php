<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\BranchContext;
use App\Support\ImpersonationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class ApplyImpersonationContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            return $next($request);
        }

        if (! ImpersonationContext::isActive($request)) {
            return $next($request);
        }

        $realUserId = ImpersonationContext::realUserId($request);
        $targetUserId = ImpersonationContext::targetUserId($request);

        if ($realUserId === null || $targetUserId === null) {
            ImpersonationContext::stop($request);

            return $next($request);
        }

        if ($authenticatedUser->id !== $realUserId && $authenticatedUser->id !== $targetUserId) {
            ImpersonationContext::stop($request);
            BranchContext::clear();

            return $next($request);
        }

        $realUser = $authenticatedUser;

        if ($authenticatedUser->id === $targetUserId) {
            $realUser = User::query()->whereKey($realUserId)->first();
        }

        if (! $realUser instanceof User || (! $realUser->isSupportUser() && ! $realUser->hasRole('super_admin'))) {
            ImpersonationContext::stop($request);
            BranchContext::clear();

            return $next($request);
        }

        $targetUser = User::query()
            ->whereKey($targetUserId)
            ->whereNotNull('tenant_id')
            ->where('is_support', false)
            ->first();

        if (! $targetUser instanceof User) {
            ImpersonationContext::stop($request);
            BranchContext::clear();
            $request->session()->flash('warning', 'The impersonated user is no longer available.');

            return $next($request);
        }

        $request->attributes->set('impersonation.real_user', $realUser);
        $request->attributes->set('impersonation.target_user', $targetUser);

        if ($request->routeIs('facility-manager.impersonation.stop')) {
            return $next($request);
        }

        Auth::setUser($targetUser);
        $request->setUserResolver(static fn (): User => $targetUser);

        return $next($request);
    }
}
