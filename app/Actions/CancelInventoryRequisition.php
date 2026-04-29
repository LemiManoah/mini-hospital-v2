<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use Illuminate\Support\Facades\Auth;

final readonly class CancelInventoryRequisition
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(InventoryRequisition $requisition, string $reason): InventoryRequisition
    {
        $updatedRows = InventoryRequisition::query()
            ->whereKey($requisition->id)
            ->whereIn('status', [
                InventoryRequisitionStatus::Draft,
                InventoryRequisitionStatus::Submitted,
            ])
            ->update([
                'status' => InventoryRequisitionStatus::Cancelled,
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only draft or submitted requisitions can be cancelled.');

        $requisition = $requisition->refresh();

        $this->recordAuditActivity->handle(
            logName: 'inventory',
            event: 'inventory.requisition.cancelled',
            subject: $requisition,
            description: 'Inventory requisition cancelled.',
            tenantId: $requisition->tenant_id,
            branchId: $requisition->branch_id,
            staffId: Auth::user()?->staff_id,
            reason: $reason,
            newValues: [
                'requisition_id' => $requisition->id,
                'status' => $requisition->status->value,
                'cancelled_by' => $requisition->cancelled_by,
                'cancelled_at' => $requisition->cancelled_at?->toISOString(),
            ],
        );

        return $requisition;
    }
}
