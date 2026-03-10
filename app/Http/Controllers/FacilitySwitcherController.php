<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilitySwitcherController
{
    public function index(Request $request): Response
    {
        $this->ensureSupportUser($request);

        $tenants = Tenant::query()
            ->with(['country', 'subscriptionPackage'])
            ->orderBy('name')
            ->get();

        return Inertia::render('facility-switcher/index', [
            'tenants' => $tenants,
        ]);
    }

    public function switch(Request $request, string $tenantId): RedirectResponse
    {
        $this->ensureSupportUser($request);

        $tenant = Tenant::query()->findOrFail($tenantId);

        /** @var User $user */
        $user = Auth::user();

        // Update user's tenant_id
        $user->update(['tenant_id' => $tenant->id]);

        // Refresh the authenticated user to pick up the new tenant_id
        Auth::setUser($user->fresh());

        // Clear any previous branch context because the tenant changed.
        BranchContext::clear();

        // Regenerate session to prevent session fixation
        $request->session()->regenerate();

        // Flash message for feedback
        $request->session()->flash('success', 'Switched to '.$tenant->name);

        // Next step is selecting a branch within the tenant.
        return to_route('branch-switcher.index');
    }

    private function ensureSupportUser(Request $request): void
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->is_support, 403, 'Only support users can switch facilities.');
    }
}
