<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSupportUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403, 'Unauthorized.');

        abort_if(! $user->is_support && ! $user->hasRole('super_admin'), 403, 'Only support users can switch facilities.');

        return $next($request);
    }
}
