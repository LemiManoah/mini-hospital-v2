<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
final class PurchaseOrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 500);
        $unitCost = fake()->randomFloat(2, 1, 10000);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity_ordered' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
            'quantity_received' => 0,
        ];
    }
}
