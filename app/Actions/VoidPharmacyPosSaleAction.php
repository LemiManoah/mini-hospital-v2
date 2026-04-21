<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosSaleStatus;
use App\Enums\StockMovementType;
use App\Models\PharmacyPosSale;
use App\Models\PharmacyPosSaleItem;
use App\Models\PharmacyPosSaleItemAllocation;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class VoidPharmacyPosSaleAction
{
    public function handle(PharmacyPosSale $sale): PharmacyPosSale
    {
        return DB::transaction(function () use ($sale): PharmacyPosSale {
            $sale = PharmacyPosSale::query()
                ->with(['items.allocations'])
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->status !== PharmacyPosSaleStatus::Completed) {
                throw ValidationException::withMessages([
                    'sale' => 'Only completed sales can be voided.',
                ]);
            }

            foreach ($sale->items as $saleItem) {
                if (! $saleItem instanceof PharmacyPosSaleItem) {
                    continue;
                }

                foreach ($saleItem->allocations as $allocation) {
                    if (! $allocation instanceof PharmacyPosSaleItemAllocation) {
                        continue;
                    }

                    StockMovement::query()->create([
                        'tenant_id' => $sale->tenant_id,
                        'branch_id' => $sale->branch_id,
                        'inventory_location_id' => $sale->inventory_location_id,
                        'inventory_item_id' => $saleItem->inventory_item_id,
                        'inventory_batch_id' => $allocation->inventory_batch_id,
                        'movement_type' => StockMovementType::PosSaleReversal,
                        'quantity' => (float) $allocation->quantity,
                        'unit_cost' => $allocation->unit_cost_snapshot,
                        'source_document_type' => PharmacyPosSale::class,
                        'source_document_id' => $sale->id,
                        'source_line_type' => PharmacyPosSaleItem::class,
                        'source_line_id' => $saleItem->id,
                        'notes' => 'Voided - stock reversed',
                        'occurred_at' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $sale->update([
                'status' => PharmacyPosSaleStatus::Cancelled,
                'updated_by' => Auth::id(),
            ]);

            return $sale->refresh();
        });
    }
}
