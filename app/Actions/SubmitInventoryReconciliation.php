<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Auth;

final readonly class SubmitInventoryReconciliation
{
    public function handle(Reconciliation $reconciliation): Reconciliation
    {
        $updatedRows = Reconciliation::query()
            ->whereKey($reconciliation->id)
            ->where('status', ReconciliationStatus::Draft)
            ->whereNull('submitted_at')
            ->whereNull('rejected_at')
            ->update([
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only draft reconciliations can be submitted.');

        return Reconciliation::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);
    }
}
