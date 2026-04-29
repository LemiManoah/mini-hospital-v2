<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use App\Models\User;
use App\Notifications\InventoryRequisitionSubmittedNotification;
use Illuminate\Support\Facades\Auth;

final readonly class SubmitInventoryRequisition
{
    public function __construct(
        private NotifyUsersWithPermission $notifyUsersWithPermission,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(InventoryRequisition $requisition): InventoryRequisition
    {
        $updatedRows = InventoryRequisition::query()
            ->whereKey($requisition->id)
            ->where('status', InventoryRequisitionStatus::Draft)
            ->update([
                'status' => InventoryRequisitionStatus::Submitted,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        abort_unless($updatedRows === 1, 422, 'Only draft requisitions can be submitted.');

        $requisition = $requisition->refresh();

        $tenantId = $requisition->getAttributeValue('tenant_id');

        if (is_string($tenantId) && $tenantId !== '') {
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'inventory',
                event: 'inventory.requisition.submitted',
                subject: $requisition,
                description: 'Inventory requisition submitted.',
                tenantId: $requisition->tenant_id,
                branchId: $requisition->branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'requisition_id' => $requisition->id,
                    'status' => $requisition->status->value,
                    'submitted_by' => $requisition->submitted_by,
                    'submitted_at' => $requisition->submitted_at?->toISOString(),
                ],
                metadata: [
                    'source_inventory_location_id' => $requisition->source_inventory_location_id,
                    'destination_inventory_location_id' => $requisition->destination_inventory_location_id,
                ],
            );

            $this->notifyUsersWithPermission->handle(
                $tenantId,
                ['inventory_requisitions.review', 'inventory_requisitions.issue'],
                new InventoryRequisitionSubmittedNotification($requisition),
            );
        }

        return $requisition;
    }
}
