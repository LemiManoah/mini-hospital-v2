<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class SwitchTenantContext
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Request $request, User $actor, string $tenantId): Tenant
    {
        throw_if(! $actor->is_support && ! $actor->hasRole('admin'), AuthorizationException::class, 'Only support users can switch facilities.');

        $tenant = Tenant::query()->findOrFail($tenantId);

        $actor->forceFill([
            'tenant_id' => $tenant->id,
        ])->save();

        $freshUser = $actor->fresh();
        if ($freshUser instanceof User) {
            Auth::setUser($freshUser);
        }

        BranchContext::clear();
        $request->session()->regenerate();
        $request->session()->flash('success', 'Switched to '.$tenant->name);

        Log::info('Support tenant context switched', [
            'actor_user_id' => $actor->id,
            'target_tenant_id' => $tenant->id,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return $tenant;
    }
}
