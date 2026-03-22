<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BootstrapOnboardingDepartments;
use App\Actions\BootstrapOnboardingStaffMember;
use App\Actions\CreateOnboardingPrimaryBranch;
use App\Actions\EnsureTenantStaffPositions;
use App\Actions\UpdateOnboardingProfile;
use App\Enums\FacilityLevel;
use App\Enums\StaffType;
use App\Http\Requests\StoreOnboardingBranchRequest;
use App\Http\Requests\StoreOnboardingDepartmentsRequest;
use App\Http\Requests\StoreOnboardingStaffRequest;
use App\Http\Requests\UpdateOnboardingProfileRequest;
use App\Models\Address;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
use App\Models\FacilityBranch;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class OnboardingController
{
    public function show(
        Request $request,
        EnsureTenantStaffPositions $ensureTenantStaffPositions,
    ): Response|RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User || $user->tenant === null) {
            return to_route('home');
        }

        if ($user->tenant->isOnboardingComplete()) {
            return to_route('home');
        }

        Gate::authorize('onboard', $user->tenant);

        $tenant = $user->tenant->loadMissing(['country', 'address']);
        $ensureTenantStaffPositions->handle($tenant);
        $currentStep = is_string($tenant->onboarding_current_step)
            ? $tenant->onboarding_current_step
            : 'profile';
        $branch = FacilityBranch::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_main_branch', true)
            ->with('address')
            ->first();
        $departments = Department::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('department_name')
            ->get(['id', 'department_name', 'location', 'is_clinical']);

        return Inertia::render('onboarding/show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'facility_level' => $tenant->facility_level->value,
                'facility_level_label' => $tenant->facility_level->label(),
                'country_id' => $tenant->country_id,
                'address_id' => $tenant->address_id,
                'address' => $tenant->address ? [
                    'id' => $tenant->address->id,
                    'city' => $tenant->address->city,
                    'district' => $tenant->address->district,
                    'state' => $tenant->address->state,
                ] : null,
            ],
            'currentStep' => $currentStep,
            'steps' => $this->steps($currentStep),
            'facilityLevels' => collect(FacilityLevel::cases())
                ->map(static fn (FacilityLevel $level): array => [
                    'value' => $level->value,
                    'label' => $level->label(),
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
            'staff_positions' => StaffPosition::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (StaffPosition $position): array => [
                    'id' => $position->id,
                    'name' => $position->name,
                ])
                ->values()
                ->all(),
            'currencies' => Currency::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'symbol'])
                ->map(static fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                ])
                ->values()
                ->all(),
            'addresses' => Address::query()
                ->orderBy('city')
                ->orderBy('district')
                ->orderBy('state')
                ->get(['id', 'city', 'district', 'state', 'country_id'])
                ->map(static fn (Address $address): array => [
                    'id' => $address->id,
                    'city' => $address->city,
                    'district' => $address->district,
                    'state' => $address->state,
                    'country_id' => $address->country_id,
                ])
                ->values()
                ->all(),
            'branch' => $branch ? [
                'name' => $branch->name,
                'branch_code' => $branch->branch_code,
                'email' => $branch->email,
                'main_contact' => $branch->main_contact,
                'other_contact' => $branch->other_contact,
                'currency_id' => $branch->currency_id,
                'has_store' => $branch->has_store,
                'address_id' => $branch->address_id,
                'address' => $branch->address ? [
                    'id' => $branch->address->id,
                    'city' => $branch->address->city,
                    'district' => $branch->address->district,
                    'state' => $branch->address->state,
                    'country_id' => $branch->address->country_id,
                ] : null,
            ] : null,
            'departments' => $departments
                ->map(static fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => $department->department_name,
                    'location' => $department->location,
                    'is_clinical' => $department->is_clinical,
                ])
                ->values()
                ->all(),
            'staffPositions' => StaffPosition::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (StaffPosition $position): array => [
                    'id' => $position->id,
                    'name' => $position->name,
                ])
                ->values()
                ->all(),
            'staffTypes' => collect(StaffType::cases())
                ->map(static fn (StaffType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function updateProfile(
        UpdateOnboardingProfileRequest $request,
        UpdateOnboardingProfile $updateOnboardingProfile,
    ): RedirectResponse {
        $user = $request->user();

        if ($user instanceof User && $user->tenant !== null) {
            Gate::authorize('onboard', $user->tenant);
            $updateOnboardingProfile->handle($user->tenant, $request->validated());
        }

        return to_route('onboarding.show')->with('success', 'Hospital profile saved.');
    }

    public function storeBranch(
        StoreOnboardingBranchRequest $request,
        CreateOnboardingPrimaryBranch $createOnboardingPrimaryBranch,
    ): RedirectResponse {
        $user = $request->user();

        if ($user instanceof User && $user->tenant !== null) {
            Gate::authorize('onboard', $user->tenant);
            $createOnboardingPrimaryBranch->handle(
                $user->tenant,
                $user,
                $request->validated(),
            );
        }

        return to_route('onboarding.show')->with('success', 'Primary branch saved.');
    }

    public function storeDepartments(
        StoreOnboardingDepartmentsRequest $request,
        BootstrapOnboardingDepartments $bootstrapOnboardingDepartments,
    ): RedirectResponse {
        $user = $request->user();

        if ($user instanceof User && $user->tenant !== null) {
            Gate::authorize('onboard', $user->tenant);
            $bootstrapOnboardingDepartments->handle(
                $user->tenant,
                $user,
                $request->validated()['departments'],
            );
        }

        return to_route('onboarding.show')->with('success', 'Departments saved. Add the first operational staff member to finish onboarding.');
    }

    public function storeStaff(
        StoreOnboardingStaffRequest $request,
        BootstrapOnboardingStaffMember $bootstrapOnboardingStaffMember,
    ): RedirectResponse {
        $user = $request->user();

        if ($user instanceof User && $user->tenant !== null) {
            Gate::authorize('onboard', $user->tenant);
            $bootstrapOnboardingStaffMember->handle(
                $user->tenant,
                $user,
                $request->validated(),
            );
        }

        return to_route('home')->with('success', 'Onboarding completed. Your workspace is ready.');
    }

    /**
     * @return array<int, array{key: string, title: string, status: string, description: string}>
     */
    private function steps(string $currentStep): array
    {
        $order = ['profile', 'branch', 'departments', 'staff', 'complete'];

        return collect([
            'profile' => [
                'title' => 'Hospital profile',
                'description' => 'Confirm the identity and core details of the hospital workspace.',
            ],
            'branch' => [
                'title' => 'Primary branch',
                'description' => 'Create the first operating branch and attach the owner to it.',
            ],
            'departments' => [
                'title' => 'Departments',
                'description' => 'Bootstrap the first operational departments for the hospital.',
            ],
            'staff' => [
                'title' => 'First staff member',
                'description' => 'Create the first operational staff profile to finish onboarding.',
            ],
        ])->map(function (array $step, string $key) use ($currentStep, $order): array {
            $currentIndex = array_search($currentStep, $order, true);
            $stepIndex = array_search($key, $order, true);

            $status = $stepIndex < $currentIndex
                ? 'complete'
                : ($stepIndex === $currentIndex ? 'current' : 'upcoming');

            return [
                'key' => $key,
                'title' => $step['title'],
                'status' => $status,
                'description' => $step['description'],
            ];
        })->values()->all();
    }
}
