<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GeneralStatus;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPackage>
 */
final class SubscriptionPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'users' => fake()->unique()->numberBetween(10, 10000),
            'price' => 1000,
            'status' => GeneralStatus::ACTIVE,
        ];
    }
}
