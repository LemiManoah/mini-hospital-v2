<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Auth;

final readonly class ApproveInventoryReconciliation
{
    public function handle(Reconciliation $reconciliation, ?string $approvalNotes = null): Reconciliation
    {
        $updatedRows = Reconciliation::query()
            ->whereKey($reconciliation->id)
            ->where('status', ReconciliationStatus::Draft)
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

        return Reconciliation::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);
    }
}
