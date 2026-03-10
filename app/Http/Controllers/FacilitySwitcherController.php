<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilitySwitcherController
{
    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->with(['country', 'subscriptionPackage'])
            ->orderBy('name')
            ->get();

        return Inertia::render('facility-switcher/index', [
            'tenants' => $tenants,
        ]);
    }

    public function switch(Request $request, string $tenantId): \Illuminate\Http\RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);
        
        /** @var User $user */
        $user = Auth::user();
        
        // Update user's tenant_id
        $user->update(['tenant_id' => $tenant->id]);
        
        // Refresh the authenticated user to pick up the new tenant_id
        Auth::setUser($user->fresh());
        
        // Regenerate session to prevent session fixation
        $request->session()->regenerate();
        
        // Flash message for feedback
        $request->session()->flash('success', "Switched to {$tenant->name}");
        
        // Redirect to the tenant's dashboard
        return redirect()->route('dashboard');
    }
}
