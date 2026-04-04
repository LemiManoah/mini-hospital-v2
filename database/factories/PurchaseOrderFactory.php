<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\FacilityBranch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
final class PurchaseOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => FacilityBranch::factory(),
            'supplier_id' => Supplier::factory(),
            'order_number' => fake()->unique()->numerify('PO-#####'),
            'status' => PurchaseOrderStatus::Draft,
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(14),
            'total_amount' => 0,
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => PurchaseOrderStatus::Submitted]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => PurchaseOrderStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
