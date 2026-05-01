<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Auth;

final readonly class RejectInventoryReconciliation
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(Reconciliation $reconciliation, string $rejectionReason): Reconciliation
    {
        $updatedRows = Reconciliation::query()
            ->whereKey($reconciliation->id)
            ->where('status', ReconciliationStatus::Draft)
            ->whereNotNull('submitted_at')
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->update([
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $rejectionReason,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only submitted or reviewed reconciliations can be rejected.');

        $reconciliation = Reconciliation::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);

        $this->recordAuditActivity->handle(
            logName: 'inventory',
            event: 'inventory.reconciliation.rejected',
            subject: $reconciliation,
            description: 'Stock reconciliation rejected.',
            tenantId: $reconciliation->tenant_id,
            branchId: $reconciliation->branch_id,
            reason: $rejectionReason,
            newValues: [
                'reconciliation_id' => $reconciliation->id,
                'rejected_at' => $reconciliation->rejected_at?->toISOString(),
            ],
        );

        return $reconciliation;
    }
}
