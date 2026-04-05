<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockAdjustmentStatus;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;

final readonly class SubmitInventoryReconciliation
{
    public function handle(StockAdjustment $reconciliation): StockAdjustment
    {
        $updatedRows = StockAdjustment::query()
            ->whereKey($reconciliation->id)
            ->where('status', StockAdjustmentStatus::Draft)
            ->whereNull('submitted_at')
            ->whereNull('rejected_at')
            ->update([
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only draft reconciliations can be submitted.');

        return StockAdjustment::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);
    }
}
