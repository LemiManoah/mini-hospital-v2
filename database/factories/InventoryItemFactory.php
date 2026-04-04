<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
final class InventoryItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'item_type' => fake()->randomElement(InventoryItemType::cases()),
            'minimum_stock_level' => 0,
            'reorder_level' => 0,
            'is_active' => true,
        ];
    }

    public function drug(): static
    {
        return $this->state([
            'item_type' => InventoryItemType::DRUG,
            'generic_name' => fake()->words(2, true),
        ]);
    }

    public function consumable(): static
    {
        return $this->state(['item_type' => InventoryItemType::CONSUMABLE]);
    }
}
