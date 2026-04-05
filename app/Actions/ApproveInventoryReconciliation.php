<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StockAdjustmentStatus;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;

final readonly class ApproveInventoryReconciliation
{
    public function handle(StockAdjustment $reconciliation, ?string $approvalNotes = null): StockAdjustment
    {
        $updatedRows = StockAdjustment::query()
            ->whereKey($reconciliation->id)
            ->where('status', StockAdjustmentStatus::Draft)
            ->whereNotNull('reviewed_at')
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->update([
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $approvalNotes !== null && $approvalNotes !== '' ? $approvalNotes : null,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only reviewed reconciliations can be approved.');

        return StockAdjustment::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);
    }
}
