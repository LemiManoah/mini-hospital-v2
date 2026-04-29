<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use Illuminate\Support\Facades\Auth;

final readonly class RejectInventoryRequisition
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(InventoryRequisition $requisition, string $reason): InventoryRequisition
    {
        $updatedRows = InventoryRequisition::query()
            ->whereKey($requisition->id)
            ->where('status', InventoryRequisitionStatus::Submitted)
            ->update([
                'status' => InventoryRequisitionStatus::Rejected,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only submitted requisitions can be rejected.');

        $requisition = $requisition->refresh();

        $this->recordAuditActivity->handle(
            logName: 'inventory',
            event: 'inventory.requisition.rejected',
            subject: $requisition,
            description: 'Inventory requisition rejected.',
            tenantId: $requisition->tenant_id,
            branchId: $requisition->branch_id,
            staffId: Auth::user()?->staff_id,
            reason: $reason,
            newValues: [
                'requisition_id' => $requisition->id,
                'status' => $requisition->status->value,
                'rejected_by' => $requisition->rejected_by,
                'rejected_at' => $requisition->rejected_at?->toISOString(),
            ],
        );

        return $requisition;
    }
}
