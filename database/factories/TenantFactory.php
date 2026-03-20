<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FacilityLevel;
use App\Enums\GeneralStatus;
use App\Models\Country;
use App\Models\SubscriptionPackage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'domain' => fake()->unique()->domainName(),
            'country_id' => Country::factory(),
            'subscription_package_id' => SubscriptionPackage::factory(),
            'facility_level' => FacilityLevel::CLINIC,
            'status' => GeneralStatus::ACTIVE,
            'onboarding_completed_at' => now(),
        ];
    }
}
