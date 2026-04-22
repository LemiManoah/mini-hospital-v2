<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Address>
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city' => $this->faker->city(),
            'district' => $this->faker->word(),
            'state' => $this->faker->word(),
            'country_id' => Country::query()->inRandomOrder()->first()->id ?? Country::factory(),
        ];
    }
}
