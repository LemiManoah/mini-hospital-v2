<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Services\SwitchTenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class FacilitySwitcherController
{
    public function __construct(
        private SwitchTenantContext $switchTenantContext,
    ) {}

    public function index(): Response
    {
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
        /** @var User $user */
        $user = $request->user();

        $this->switchTenantContext->handle($request, $user, $tenantId);

        // Next step is selecting a branch within the tenant.
        return to_route('branch-switcher.index');
    }
}
