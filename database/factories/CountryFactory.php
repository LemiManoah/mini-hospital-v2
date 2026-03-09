<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
final class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_name' => $this->faker->country(),
            'country_code' => $this->faker->unique()->countryCode(),
            'dial_code' => '+' . $this->faker->numberBetween(1, 999),
            'currency' => $this->faker->currencyCode(),
            'currency_symbol' => $this->faker->currencyCode(), // Faker doesn't have a direct symbol method that's reliable
        ];
    }
}
