<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Models\Currency;
use App\Models\TenantGeneralSetting;
use App\Support\GeneralSettings\GeneralSettingsRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AdministrationController
{
    public function generalSettings(Request $request): Response
    {
        $this->abortUnlessCanAccess($request, $this->generalSettingsPermissions());

        $tenantId = $request->user()?->tenant_id;

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);

        $storedValues = TenantGeneralSetting::query()
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->all();

        return Inertia::render('administration/general-settings', [
            'sections' => GeneralSettingsRegistry::sections(),
            'values' => GeneralSettingsRegistry::resolveValues($storedValues),
            'currencies' => Currency::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'symbol'])
                ->map(static fn (Currency $currency): array => [
                    'value' => $currency->id,
                    'label' => sprintf(
                        '%s (%s%s)',
                        $currency->name,
                        $currency->code,
                        $currency->symbol !== null && $currency->symbol !== ''
                            ? ' - '.$currency->symbol
                            : '',
                    ),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function updateGeneralSettings(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->abortUnlessCanAccess($request, $this->generalSettingsPermissions());

        $tenantId = $request->user()?->tenant_id;

        abort_unless(is_string($tenantId) && $tenantId !== '', 403);

        foreach (GeneralSettingsRegistry::serializeValues($request->validated()) as $key => $value) {
            TenantGeneralSetting::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                ],
            );
        }

        return to_route('administration.general-settings')
            ->with('success', 'General settings updated successfully.');
    }

    public function insuranceSetup(Request $request): Response
    {
        return $this->renderSection(
            $request,
            'Insurance Setup',
            'Configure payer organizations and the packages available for billing.',
            [
                [
                    'title' => 'Insurance Companies',
                    'description' => 'Maintain payer records used across registration and billing.',
                    'href' => '/insurance-companies',
                    'permission' => 'insurance_companies.view',
                ],
                [
                    'title' => 'Insurance Packages',
                    'description' => 'Manage package definitions and company-linked plan options.',
                    'href' => '/insurance-packages',
                    'permission' => 'insurance_packages.view',
                ],
            ],
        );
    }

    public function masterData(Request $request): Response
    {
        return $this->renderSection(
            $request,
            'Master Data',
            'Maintain reusable catalogs, reference lists, and setup records used across the application.',
            [
                [
                    'title' => 'Addresses',
                    'description' => 'Manage address records and reusable address references.',
                    'href' => '/addresses',
                    'permission' => 'addresses.view',
                ],
                [
                    'title' => 'Allergens',
                    'description' => 'Maintain the allergen list used for patient safety and documentation.',
                    'href' => '/allergens',
                    'permission' => 'allergens.view',
                ],
                [
                    'title' => 'Currencies',
                    'description' => 'Define supported currencies for the facility and its billing workflows.',
                    'href' => '/currencies',
                    'permission' => 'currencies.view',
                ],
                [
                    'title' => 'Units',
                    'description' => 'Manage measurement units used across inventory, pharmacy, and laboratory.',
                    'href' => '/units',
                    'permission' => 'units.view',
                ],
                [
                    'title' => 'Clinics',
                    'description' => 'Manage outpatient and specialty clinic definitions.',
                    'href' => '/clinics',
                    'permission' => 'clinics.view',
                ],
                [
                    'title' => 'Departments',
                    'description' => 'Maintain departmental structure for staffing and reporting.',
                    'href' => '/departments',
                    'permission' => 'departments.view',
                ],
                [
                    'title' => 'Facility Services',
                    'description' => 'Manage chargeable services, pricing, and service availability.',
                    'href' => '/facility-services',
                    'permission' => 'facility_services.view',
                ],
            ],
        );
    }

    public function platform(Request $request): Response
    {
        $user = $request->user();

        $items = collect([
            [
                'title' => 'Facility Branches',
                'description' => 'Create and maintain facility branches, branch metadata, and activation details.',
                'href' => '/facility-branches',
                'visible' => $user?->can('facility_branches.view') ?? false,
            ],
            [
                'title' => 'Subscription Packages',
                'description' => 'Manage SaaS plan definitions and subscription package metadata.',
                'href' => '/subscription-packages',
                'visible' => $user?->can('subscription_packages.view') ?? false,
            ],
            [
                'title' => 'Facility Manager',
                'description' => 'Detailed support panel for facility status, onboarding, subscription state, and workspace intervention.',
                'href' => '/facility-manager/dashboard',
                'visible' => (bool) ($user?->is_support ?? false) || ($user?->hasRole('super_admin') ?? false),
            ],
        ])->filter(static fn (array $item): bool => $item['visible'])->values();

        abort_if($items->isEmpty(), 403);

        return Inertia::render('administration/section', [
            'title' => 'Platform',
            'description' => 'Support and SaaS-level controls that should stay separate from ordinary hospital administration.',
            'items' => $items
                ->map(static fn (array $item): array => [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'href' => $item['href'],
                ])
                ->all(),
        ]);
    }

    /**
     * @param  list<array{title: string, description: string, href: string, permission: string}>  $items
     */
    private function renderSection(
        Request $request,
        string $title,
        string $description,
        array $items,
    ): Response {
        $user = $request->user();

        $visibleItems = collect($items)
            ->filter(static fn (array $item): bool => $user?->can($item['permission']) ?? false)
            ->map(static fn (array $item): array => [
                'title' => $item['title'],
                'description' => $item['description'],
                'href' => $item['href'],
            ])
            ->values();

        abort_if($visibleItems->isEmpty(), 403);

        return Inertia::render('administration/section', [
            'title' => $title,
            'description' => $description,
            'items' => $visibleItems->all(),
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function abortUnlessCanAccess(Request $request, array $permissions): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && collect($permissions)->contains(
                static fn (string $permission): bool => $user->can($permission),
            ),
            403,
        );
    }

    /**
     * @return list<string>
     */
    private function generalSettingsPermissions(): array
    {
        return [
            'facility_branches.view',
            'clinics.view',
            'departments.view',
            'facility_services.view',
            'insurance_companies.view',
            'insurance_packages.view',
            'addresses.view',
            'allergens.view',
            'currencies.view',
            'units.view',
            'subscription_packages.view',
            'tenants.view',
        ];
    }
}
