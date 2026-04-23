<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PharmacyPosSaleStatus;
use App\Enums\StockMovementType;
use App\Models\PharmacyPosSale;
use App\Models\PharmacyPosSaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RefundPharmacyPosSaleAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(PharmacyPosSale $sale, array $data): PharmacyPosSale
    {
        return DB::transaction(function () use ($sale, $data): PharmacyPosSale {
            $sale = PharmacyPosSale::query()
                ->with(['items.allocations'])
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($sale->status !== PharmacyPosSaleStatus::Completed) {
                throw ValidationException::withMessages([
                    'sale' => 'Only completed sales can be refunded.',
                ]);
            }

            foreach ($sale->items as $saleItem) {
                foreach ($saleItem->allocations as $allocation) {
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
                        'notes' => 'Refunded — stock reversed',
                        'occurred_at' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            $refundAmount = $this->floatValue($data['refund_amount'] ?? $sale->paid_amount);

            $sale->payments()->create([
                'amount' => $refundAmount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'payment_date' => now(),
                'is_refund' => true,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $sale->update([
                'status' => PharmacyPosSaleStatus::Refunded,
                'updated_by' => Auth::id(),
            ]);

            return $sale->refresh();
        });
    }

    private function floatValue(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }
}
