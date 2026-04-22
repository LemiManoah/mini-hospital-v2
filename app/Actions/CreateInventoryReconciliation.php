<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateInventoryReconciliation
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    /**
     * @param  array{
     *      tenant_id?: string,
     *      branch_id?: string,
     *      inventory_location_id: string,
     *      reconciliation_date: string,
     *      reason: string,
     *      notes?: string|null
     *  }  $attributes
     * @param  list<array{
     *      inventory_item_id: string,
     *      actual_quantity: float|int|string,
     *      unit_cost: float|int|string,
     *      inventory_batch_id?: string|null,
     *      batch_number?: string|null,
     *      expiry_date?: string|null,
     *      notes?: string|null
     *  }>  $items
     */
    public function handle(array $attributes, array $items): Reconciliation
    {
        return DB::transaction(function () use ($attributes, $items): Reconciliation {
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
                        $balance['inventory_item_id'] => (float) $balance['quantity'],
                    ])
                : collect();

            $reconciliation = Reconciliation::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'inventory_location_id' => $locationId,
                'adjustment_number' => $this->generateReconciliationNumber($tenantId),
                'status' => ReconciliationStatus::Draft,
                'adjustment_date' => $attributes['reconciliation_date'],
                'reason' => $attributes['reason'],
                'notes' => ($attributes['notes'] ?? '') !== '' ? $attributes['notes'] : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $inventoryItemId = $item['inventory_item_id'];
                $expectedQuantity = (float) ($currentQuantities[$inventoryItemId] ?? 0.0);
                $actualQuantity = (float) $item['actual_quantity'];
                $varianceQuantity = $actualQuantity - $expectedQuantity;

                $reconciliation->items()->create([
                    'inventory_item_id' => $inventoryItemId,
                    'inventory_batch_id' => ($item['inventory_batch_id'] ?? '') !== '' ? $item['inventory_batch_id'] : null,
                    'expected_quantity' => $expectedQuantity,
                    'actual_quantity' => $actualQuantity,
                    'variance_quantity' => $varianceQuantity,
                    'quantity_delta' => $varianceQuantity,
                    'unit_cost' => $item['unit_cost'],
                    'batch_number' => ($item['batch_number'] ?? '') !== '' ? $item['batch_number'] : null,
                    'expiry_date' => ($item['expiry_date'] ?? '') !== '' ? $item['expiry_date'] : null,
                    'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
                ]);
            }

            return $reconciliation->refresh()->load('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation');
        });
    }

    private function generateReconciliationNumber(?string $tenantId): string
    {
        do {
            $reconciliationNumber = 'REC-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (
            $tenantId !== null
            && Reconciliation::query()->where('tenant_id', $tenantId)->where('adjustment_number', $reconciliationNumber)->exists()
        );

        return $reconciliationNumber;
    }
}
