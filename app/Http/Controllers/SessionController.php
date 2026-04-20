<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateSessionRequest;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SessionController
{
    public function create(Request $request): Response
    {
        return Inertia::render('session/create', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(CreateSessionRequest $request): RedirectResponse
    {
        $user = $request->validateCredentials();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return to_route('two-factor.login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        if ($user->isSupportUser()) {
            BranchContext::clear();

            return to_route('facility-manager.dashboard');
        }

        if ($user->tenant !== null && ! $user->tenant->isOnboardingComplete()) {
            BranchContext::clear();

            return to_route('onboarding.show');
        }

        $accessibleBranches = BranchContext::getAccessibleBranches($user);

        if ($accessibleBranches->isNotEmpty()) {
            BranchContext::setActiveBranchId((string) $accessibleBranches->first()->id);
        } else {
            BranchContext::clear();
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        BranchContext::clear();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
