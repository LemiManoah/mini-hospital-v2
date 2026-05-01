<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\Auth;

final readonly class ReviewInventoryReconciliation
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(Reconciliation $reconciliation, ?string $reviewNotes = null): Reconciliation
    {
        $updatedRows = Reconciliation::query()
            ->whereKey($reconciliation->id)
            ->where('status', ReconciliationStatus::Draft)
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

        $reconciliation = Reconciliation::query()
            ->with('items.inventoryItem', 'items.inventoryBatch', 'inventoryLocation')
            ->findOrFail($reconciliation->id);

        $this->recordAuditActivity->handle(
            logName: 'inventory',
            event: 'inventory.reconciliation.reviewed',
            subject: $reconciliation,
            description: 'Stock reconciliation reviewed.',
            tenantId: $reconciliation->tenant_id,
            branchId: $reconciliation->branch_id,
            reason: $reviewNotes,
            newValues: [
                'reconciliation_id' => $reconciliation->id,
                'reviewed_at' => $reconciliation->reviewed_at?->toISOString(),
            ],
        );

        return $reconciliation;
    }
}
