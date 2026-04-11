<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AdministrationController
{
    public function generalSettings(Request $request): Response
    {
        $this->abortUnlessCanAccess(
            $request,
            [
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
            ],
        );

        return Inertia::render('administration/general-settings', [
            'categories' => [
                [
                    'title' => 'Billing And Payment Rules',
                    'description' => 'Control when services can be rendered relative to payment and insurance handling.',
                    'examples' => [
                        'Require payment before consultation, laboratory, pharmacy, or procedures',
                        'Allow insured patients to bypass upfront payment',
                        'Require bill settlement before discharge',
                    ],
                ],
                [
                    'title' => 'Currency And Pricing',
                    'description' => 'Define the facility currency, display behavior, and pricing defaults.',
                    'examples' => [
                        'Default operating currency',
                        'Decimal precision and rounding behavior',
                        'Price override and tax display rules',
                    ],
                ],
                [
                    'title' => 'Laboratory And Pharmacy Rules',
                    'description' => 'Centralize operational controls that should differ by hospital policy.',
                    'examples' => [
                        'Require review before lab result release',
                        'Enable batch tracking during dispensing',
                        'Allow partial dispensing and substitution with reasons',
                    ],
                ],
                [
                    'title' => 'Clinical And Registration Rules',
                    'description' => 'Set workflow guardrails for triage, consultation, numbering, and document output.',
                    'examples' => [
                        'Require triage before consultation',
                        'Patient, visit, receipt, and lab numbering formats',
                        'Print layout and signature defaults',
                    ],
                ],
            ],
        ]);
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
}
