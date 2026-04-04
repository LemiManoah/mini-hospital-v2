<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
final class GoodsReceiptItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity_received' => fake()->randomFloat(3, 1, 100),
            'unit_cost' => fake()->randomFloat(2, 1, 10000),
            'batch_number' => fake()->bothify('BATCH-####??'),
            'expiry_date' => now()->addMonths(fake()->numberBetween(6, 36)),
        ];
    }
}
