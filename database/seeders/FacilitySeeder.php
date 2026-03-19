<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Address;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Database\Seeder;
use RuntimeException;

final class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $package = SubscriptionPackage::query()->first();

        if (! $package instanceof SubscriptionPackage) {
            throw new RuntimeException('FacilitySeeder requires at least one subscription package.');
        }

        $countries = Country::query()
            ->whereIn('country_code', ['RW', 'UG', 'KE'])
            ->get()
            ->keyBy('country_code');

        $currencies = Currency::query()
            ->whereIn('code', ['RWF', 'UGX', 'KES'])
            ->get()
            ->keyBy('code');

        foreach (['RW', 'UG', 'KE'] as $countryCode) {
            if (! $countries->has($countryCode)) {
                throw new RuntimeException("FacilitySeeder requires country [{$countryCode}] to be seeded first.");
            }
        }

        foreach (['RWF', 'UGX', 'KES'] as $currencyCode) {
            if (! $currencies->has($currencyCode)) {
                throw new RuntimeException("FacilitySeeder requires currency [{$currencyCode}] to be seeded first.");
            }
        }

        foreach ($this->facilityBlueprints() as $facility) {
            /** @var Country $country */
            $country = $countries->get($facility['country_code']);

            /** @var Currency $currency */
            $currency = $currencies->get($facility['currency_code']);

            $tenantAddress = $this->upsertAddress($facility['address'], $country);

            $tenant = Tenant::query()->updateOrCreate(
                ['domain' => $facility['domain']],
                [
                    'name' => $facility['name'],
                    'has_branches' => $facility['has_branches'],
                    'subscription_package_id' => $package->id,
                    'status' => GeneralStatus::ACTIVE->value,
                    'country_id' => $country->id,
                    'address_id' => $tenantAddress->id,
                    'facility_level' => $facility['facility_level']->value,
                    'longitude' => $facility['longitude'],
                    'latitude' => $facility['latitude'],
                    'onboarding_completed_at' => $facility['onboarding_completed_at'],
                    'onboarding_current_step' => $facility['onboarding_current_step'],
                ],
            );

            $this->upsertSubscription($tenant, $package, $facility['subscription']);

            foreach ($facility['branches'] as $branch) {
                $branchAddress = $this->upsertAddress($branch['address'], $country);

                FacilityBranch::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'branch_code' => $branch['branch_code'],
                    ],
                    [
                        'name' => $branch['name'],
                        'address_id' => $branchAddress->id,
                        'currency_id' => $currency->id,
                        'status' => GeneralStatus::ACTIVE->value,
                        'is_main_branch' => $branch['is_main_branch'],
                        'has_store' => $branch['has_store'],
                        'main_contact' => $branch['main_contact'],
                        'other_contact' => $branch['other_contact'],
                        'email' => $branch['email'],
                    ],
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function facilityBlueprints(): array
    {
        return [
            [
                'name' => 'Kigali Heights Referral Hospital',
                'domain' => 'kigaliheights',
                'country_code' => 'RW',
                'currency_code' => 'RWF',
                'has_branches' => true,
                'facility_level' => FacilityLevel::REFERRAL_HOSPITAL,
                'longitude' => 30.0588,
                'latitude' => -1.9441,
                'onboarding_completed_at' => now()->subDays(20),
                'onboarding_current_step' => 'completed',
                'subscription' => [
                    'status' => SubscriptionStatus::ACTIVE,
                    'starts_at' => now()->subDays(20),
                    'trial_ends_at' => now()->subDays(6),
                    'activated_at' => now()->subDays(7),
                    'current_period_starts_at' => now()->subDays(7),
                    'current_period_ends_at' => now()->addDays(23),
                    'meta' => [
                        'source' => 'facility_seeder',
                        'seed_scenario' => 'active_multi_branch',
                    ],
                ],
                'address' => [
                    'city' => 'Kigali',
                    'district' => 'Gasabo',
                    'state' => 'Kigali City',
                ],
                'branches' => [
                    [
                        'branch_code' => 'KHRH-MAIN',
                        'name' => 'Kigali Heights Main Campus',
                        'is_main_branch' => true,
                        'has_store' => true,
                        'main_contact' => '+250 788 100001',
                        'other_contact' => '+250 788 100002',
                        'email' => 'main@kigaliheights.rw',
                        'address' => [
                            'city' => 'Kigali',
                            'district' => 'Gasabo',
                            'state' => 'Kigali City',
                        ],
                    ],
                    [
                        'branch_code' => 'KHRH-REM',
                        'name' => 'Kigali Heights Remera Clinic',
                        'is_main_branch' => false,
                        'has_store' => true,
                        'main_contact' => '+250 788 110001',
                        'other_contact' => '+250 788 110002',
                        'email' => 'remera@kigaliheights.rw',
                        'address' => [
                            'city' => 'Kigali',
                            'district' => 'Kicukiro',
                            'state' => 'Kigali City',
                        ],
                    ],
                    [
                        'branch_code' => 'KHRH-MUS',
                        'name' => 'Kigali Heights Musanze Outreach',
                        'is_main_branch' => false,
                        'has_store' => false,
                        'main_contact' => '+250 788 120001',
                        'other_contact' => '+250 788 120002',
                        'email' => 'musanze@kigaliheights.rw',
                        'address' => [
                            'city' => 'Musanze',
                            'district' => 'Musanze',
                            'state' => 'Northern Province',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'City General Hospital',
                'domain' => 'citygeneral',
                'country_code' => 'UG',
                'currency_code' => 'UGX',
                'has_branches' => true,
                'facility_level' => FacilityLevel::HOSPITAL,
                'longitude' => 32.5726,
                'latitude' => 0.3166,
                'onboarding_completed_at' => now()->subDays(12),
                'onboarding_current_step' => 'completed',
                'subscription' => [
                    'status' => SubscriptionStatus::PENDING_ACTIVATION,
                    'starts_at' => now()->subDays(10),
                    'trial_ends_at' => now()->addDays(4),
                    'activated_at' => null,
                    'current_period_starts_at' => now()->subDays(10),
                    'current_period_ends_at' => now()->addDays(4),
                    'meta' => [
                        'source' => 'facility_seeder',
                        'seed_scenario' => 'pending_checkout_multi_branch',
                    ],
                ],
                'address' => [
                    'city' => 'Kampala',
                    'district' => 'Kampala Central',
                    'state' => 'Central',
                ],
                'branches' => [
                    [
                        'branch_code' => 'CGH-MAIN',
                        'name' => 'City General Hospital - Main Branch',
                        'is_main_branch' => true,
                        'has_store' => true,
                        'main_contact' => '+256 414 123456',
                        'other_contact' => '+256 414 123457',
                        'email' => 'main@citygeneral.ug',
                        'address' => [
                            'city' => 'Kampala',
                            'district' => 'Kampala Central',
                            'state' => 'Central',
                        ],
                    ],
                    [
                        'branch_code' => 'CGH-ENT',
                        'name' => 'City General Hospital - Entebbe Branch',
                        'is_main_branch' => false,
                        'has_store' => true,
                        'main_contact' => '+256 414 234567',
                        'other_contact' => '+256 414 234568',
                        'email' => 'entebbe@citygeneral.ug',
                        'address' => [
                            'city' => 'Entebbe',
                            'district' => 'Wakiso',
                            'state' => 'Central',
                        ],
                    ],
                    [
                        'branch_code' => 'CGH-MUK',
                        'name' => 'City General Hospital - Mukono Branch',
                        'is_main_branch' => false,
                        'has_store' => false,
                        'main_contact' => '+256 414 345678',
                        'other_contact' => '+256 414 345679',
                        'email' => 'mukono@citygeneral.ug',
                        'address' => [
                            'city' => 'Mukono',
                            'district' => 'Mukono',
                            'state' => 'Central',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Nairobi Medical Center',
                'domain' => 'nairoimedical',
                'country_code' => 'KE',
                'currency_code' => 'KES',
                'has_branches' => false,
                'facility_level' => FacilityLevel::HEALTH_CENTER_III,
                'longitude' => 36.8219,
                'latitude' => -1.2921,
                'onboarding_completed_at' => null,
                'onboarding_current_step' => 'departments',
                'subscription' => [
                    'status' => SubscriptionStatus::TRIAL,
                    'starts_at' => now()->subDays(3),
                    'trial_ends_at' => now()->addDays(11),
                    'activated_at' => null,
                    'current_period_starts_at' => now()->subDays(3),
                    'current_period_ends_at' => now()->addDays(11),
                    'meta' => [
                        'source' => 'facility_seeder',
                        'seed_scenario' => 'trial_single_branch_mid_onboarding',
                    ],
                ],
                'address' => [
                    'city' => 'Nairobi',
                    'district' => 'Nairobi County',
                    'state' => 'Nairobi',
                ],
                'branches' => [
                    [
                        'branch_code' => 'NMC-MAIN',
                        'name' => 'Nairobi Medical Center',
                        'is_main_branch' => true,
                        'has_store' => true,
                        'main_contact' => '+254 20 123456',
                        'other_contact' => '+254 20 123457',
                        'email' => 'info@nairoimedical.ke',
                        'address' => [
                            'city' => 'Nairobi',
                            'district' => 'Nairobi County',
                            'state' => 'Nairobi',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array{city: string, district: string, state: string} $address
     */
    private function upsertAddress(array $address, Country $country): Address
    {
        return Address::query()->updateOrCreate(
            [
                'city' => $address['city'],
                'district' => $address['district'],
                'country_id' => $country->id,
            ],
            [
                'state' => $address['state'],
            ],
        );
    }

    /**
     * @param array{
     *     status: SubscriptionStatus,
     *     starts_at: \Illuminate\Support\Carbon,
     *     trial_ends_at: \Illuminate\Support\Carbon|null,
     *     activated_at: \Illuminate\Support\Carbon|null,
     *     current_period_starts_at: \Illuminate\Support\Carbon|null,
     *     current_period_ends_at: \Illuminate\Support\Carbon|null,
     *     meta: array<string, mixed>
     * } $subscription
     */
    private function upsertSubscription(Tenant $tenant, SubscriptionPackage $package, array $subscription): void
    {
        TenantSubscription::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'subscription_package_id' => $package->id,
            ],
            [
                'status' => $subscription['status']->value,
                'starts_at' => $subscription['starts_at'],
                'trial_ends_at' => $subscription['trial_ends_at'],
                'activated_at' => $subscription['activated_at'],
                'current_period_starts_at' => $subscription['current_period_starts_at'],
                'current_period_ends_at' => $subscription['current_period_ends_at'],
                'meta' => $subscription['meta'],
            ],
        );
    }
}
