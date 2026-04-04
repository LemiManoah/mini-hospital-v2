<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GeneralStatus;
use App\Models\Currency;
use App\Models\FacilityBranch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacilityBranch>
 */
final class FacilityBranchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company() . ' Branch',
            'branch_code' => fake()->unique()->bothify('BR-###'),
            'currency_id' => Currency::factory(),
            'status' => GeneralStatus::ACTIVE,
            'is_main_branch' => true,
            'has_store' => true,
        ];
    }
}
