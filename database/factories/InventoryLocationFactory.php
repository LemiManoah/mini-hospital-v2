<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InventoryLocationType;
use App\Models\FacilityBranch;
use App\Models\InventoryLocation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLocation>
 */
final class InventoryLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => FacilityBranch::factory(),
            'name' => fake()->words(2, true).' Store',
            'location_code' => fake()->unique()->bothify('LOC-###'),
            'type' => InventoryLocationType::MAIN_STORE,
            'is_active' => true,
        ];
    }

    public function pharmacy(): static
    {
        return $this->state([
            'type' => InventoryLocationType::PHARMACY,
            'is_dispensing_point' => true,
        ]);
    }
}
