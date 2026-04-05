<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockAdjustmentStatus;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateStockAdjustment
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function handle(array $attributes, array $items): StockAdjustment
    {
        return DB::transaction(function () use ($attributes, $items): StockAdjustment {
            $tenantId = is_string($attributes['tenant_id'] ?? null)
                ? $attributes['tenant_id']
                : Auth::user()?->tenantId();

            $adjustment = StockAdjustment::query()->create([
                ...$attributes,
                'adjustment_number' => $this->generateAdjustmentNumber($tenantId),
                'status' => StockAdjustmentStatus::Draft,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $adjustment->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'inventory_batch_id' => ($item['inventory_batch_id'] ?? '') !== '' ? $item['inventory_batch_id'] : null,
                    'quantity_delta' => $item['quantity_delta'],
                    'unit_cost' => $item['unit_cost'],
                    'batch_number' => ($item['batch_number'] ?? '') !== '' ? $item['batch_number'] : null,
                    'expiry_date' => ($item['expiry_date'] ?? '') !== '' ? $item['expiry_date'] : null,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                ]);
            }

            return $adjustment->refresh()->load('items.inventoryItem', 'items.inventoryBatch');
        });
    }

    private function generateAdjustmentNumber(?string $tenantId): string
    {
        do {
            $adjustmentNumber = 'ADJ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && StockAdjustment::query()->where('tenant_id', $tenantId)->where('adjustment_number', $adjustmentNumber)->exists()
        );

        return $adjustmentNumber;
    }
}
