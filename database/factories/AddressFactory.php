<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    #[Override]
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
            'state' => $this->faker->state(),
            'country_id' => Country::query()->inRandomOrder()->first()?->id ?? Country::factory(),
        ];
    }
}
