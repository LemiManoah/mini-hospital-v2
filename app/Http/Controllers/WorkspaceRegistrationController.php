<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterWorkspace;
use App\Enums\FacilityLevel;
use App\Http\Requests\StoreWorkspaceRegistrationRequest;
use App\Models\Country;
use App\Models\SubscriptionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WorkspaceRegistrationController
{
    public function create(): Response
    {
        return Inertia::render('saas/register', [
            'facilityLevels' => collect(FacilityLevel::cases())
                ->map(static fn (FacilityLevel $level): array => [
                    'value' => $level->value,
                    'label' => $level->label(),
                ])
                ->values()
                ->all(),
            'subscriptionPackages' => SubscriptionPackage::query()
                ->orderBy('users')
                ->orderBy('name')
                ->get(['id', 'name', 'users', 'price'])
                ->map(static fn (SubscriptionPackage $package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'users' => $package->users,
                    'price' => $package->price,
                ])
                ->values()
                ->all(),
            'countries' => Country::query()
                ->orderBy('country_name')
                ->get(['id', 'country_name'])
                ->map(static fn (Country $country): array => [
                    'id' => $country->id,
                    'name' => $country->country_name,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(
        StoreWorkspaceRegistrationRequest $request,
        RegisterWorkspace $registerWorkspace,
    ): RedirectResponse {
        $workspace = $registerWorkspace->handle($request->createDto(), $request->password());

        Auth::login($workspace['user']);
        $request->session()->regenerate();

        return to_route('onboarding.show')
            ->with('success', 'Workspace created successfully. Let us finish your setup.');
    }
}
