<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockCountStatus;
use App\Models\StockCount;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateStockCount
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items): StockCount
    {
        return DB::transaction(function () use ($attributes, $items): StockCount {
            $tenantId = is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : Auth::user()?->tenantId();
            $branchId = is_string($attributes['branch_id'] ?? null)
                ? $attributes['branch_id']
                : BranchContext::getActiveBranchId();
            $locationId = (string) $attributes['inventory_location_id'];

            $currentQuantities = is_string($branchId) && $branchId !== ''
                ? $this->inventoryStockLedger
                    ->summarizeByLocation($branchId)
                    ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId)
                    ->mapWithKeys(static fn (array $balance): array => [
                        $balance['inventory_item_id'] => $balance['quantity'],
                    ])
                : collect();

            $stockCount = StockCount::query()->create([
                ...$attributes,
                'count_number' => $this->generateCountNumber($tenantId),
                'status' => StockCountStatus::Draft,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $inventoryItemId = (string) $item['inventory_item_id'];
                $expectedQuantity = (float) ($currentQuantities[$inventoryItemId] ?? 0.0);
                $countedQuantity = (float) $item['counted_quantity'];

                $stockCount->items()->create([
                    'inventory_item_id' => $inventoryItemId,
                    'expected_quantity' => $expectedQuantity,
                    'counted_quantity' => $countedQuantity,
                    'variance_quantity' => $countedQuantity - $expectedQuantity,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                ]);
            }

            return $stockCount->refresh()->load('items.inventoryItem', 'inventoryLocation');
        });
    }

    private function generateCountNumber(?string $tenantId): string
    {
        do {
            $countNumber = 'CNT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && StockCount::query()->where('tenant_id', $tenantId)->where('count_number', $countNumber)->exists()
        );

        return $countNumber;
    }
}
