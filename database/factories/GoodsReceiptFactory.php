<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Models\FacilityBranch;
use App\Models\GoodsReceipt;
use App\Models\InventoryLocation;
use App\Models\PurchaseOrder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
final class GoodsReceiptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => FacilityBranch::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'inventory_location_id' => InventoryLocation::factory(),
            'receipt_number' => fake()->unique()->numerify('GR-#####'),
            'status' => GoodsReceiptStatus::Draft,
            'receipt_date' => now(),
        ];
    }

    public function posted(): static
    {
        return $this->state([
            'status' => GoodsReceiptStatus::Posted,
            'posted_at' => now(),
        ]);
    }
}
