<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordAuditActivity;
use App\Http\Requests\CreateSessionRequest;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\ImpersonationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SessionController
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

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

        $accessibleBranches = BranchContext::getAccessibleBranches($user);

        if ($user->isSupportUser()) {
            if ($accessibleBranches->isNotEmpty()) {
                BranchContext::setActiveBranchId((string) $accessibleBranches->first()->id);
            } else {
                BranchContext::clear();
            }

            $this->recordAuditActivity->handle(
                logName: 'access',
                event: 'access.login.succeeded',
                subject: $user,
                description: 'User logged in.',
                actor: $user,
                tenantId: $user->tenantId(),
                branchId: BranchContext::getActiveBranchId(),
                staffId: $user->staffId(),
                metadata: [
                    'remember' => $request->boolean('remember'),
                ],
            );

            return $user->can('tenants.impersonate')
                ? to_route('facility-manager.impersonation.index')
                : to_route('facility-manager.dashboard');
        }

        if ($user->tenant !== null && ! $user->tenant->isOnboardingComplete()) {
            BranchContext::clear();

            return to_route('onboarding.show');
        }

        if ($accessibleBranches->isNotEmpty()) {
            BranchContext::setActiveBranchId((string) $accessibleBranches->first()->id);
        } else {
            BranchContext::clear();
        }

        $this->recordAuditActivity->handle(
            logName: 'access',
            event: 'access.login.succeeded',
            subject: $user,
            description: 'User logged in.',
            actor: $user,
            tenantId: $user->tenantId(),
            branchId: BranchContext::getActiveBranchId(),
            staffId: $user->staffId(),
            metadata: [
                'remember' => $request->boolean('remember'),
            ],
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->recordAuditActivity->handle(
                logName: 'access',
                event: 'access.logout',
                subject: $user,
                description: 'User logged out.',
                actor: $user,
                tenantId: $user->tenantId(),
                branchId: BranchContext::getActiveBranchId(),
                staffId: $user->staffId(),
            );
        }

        BranchContext::clear();
        ImpersonationContext::stop($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
