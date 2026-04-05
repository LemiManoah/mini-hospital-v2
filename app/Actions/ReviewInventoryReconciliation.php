<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockAdjustmentStatus;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;

final readonly class ReviewInventoryReconciliation
{
    public function handle(StockAdjustment $reconciliation, ?string $reviewNotes = null): StockAdjustment
    {
        $updatedRows = StockAdjustment::query()
            ->whereKey($reconciliation->id)
            ->where('status', StockAdjustmentStatus::Draft)
            ->whereNotNull('submitted_at')
            ->whereNull('reviewed_at')
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->update([
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes !== null && $reviewNotes !== '' ? $reviewNotes : null,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only submitted reconciliations can be reviewed.');

        return StockAdjustment::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);
    }
}
