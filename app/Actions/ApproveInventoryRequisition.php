<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\ApproveInventoryRequisitionDTO;
use App\Enums\InventoryRequisitionStatus;
use App\Models\InventoryRequisition;
use App\Models\InventoryRequisitionItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ApproveInventoryRequisition
{
    public function __construct(private RecordAuditActivity $recordAuditActivity) {}

    public function handle(InventoryRequisition $requisition, ApproveInventoryRequisitionDTO $data): InventoryRequisition
    {
        return DB::transaction(function () use ($requisition, $data): InventoryRequisition {
            /** @var InventoryRequisition $requisition */
            $requisition = InventoryRequisition::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($requisition->id);

            abort_unless(
                $requisition->status === InventoryRequisitionStatus::Submitted,
                422,
                'Only submitted requisitions can be approved.',
            );

            $approvedQuantities = collect($data->itemAttributes())
                ->mapWithKeys(static fn (array $item): array => [
                    $item['inventory_requisition_item_id'] => is_numeric($item['approved_quantity'])
                        ? (float) $item['approved_quantity']
                        : 0.0,
                ]);

            abort_if(
                $approvedQuantities->every(static fn (float $quantity): bool => $quantity <= 0),
                422,
                'Approve at least one line quantity before continuing.',
            );

            /** @var Collection<int, InventoryRequisitionItem> $requisitionItems */
            $requisitionItems = $requisition->items;

            foreach ($requisitionItems as $item) {
                $item->update([
                    'approved_quantity' => $approvedQuantities[$item->id] ?? 0,
                ]);
            }

            $requisition->update([
                'status' => InventoryRequisitionStatus::Approved,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $data->approvalNotes,
                'updated_by' => Auth::id(),
            ]);

            $this->recordAuditActivity->handle(
                logName: 'inventory',
                event: 'inventory.requisition.approved',
                subject: $requisition,
                description: 'Inventory requisition approved.',
                tenantId: $requisition->tenant_id,
                branchId: $requisition->branch_id,
                staffId: Auth::user()?->staff_id,
                newValues: [
                    'requisition_id' => $requisition->id,
                    'status' => $requisition->status->value,
                    'approved_by' => $requisition->approved_by,
                    'approved_at' => $requisition->approved_at?->toISOString(),
                    'approved_line_count' => $approvedQuantities->filter(
                        static fn (float $quantity): bool => $quantity > 0
                    )->count(),
                    'approved_quantity_total' => round($approvedQuantities->sum(), 3),
                ],
                metadata: [
                    'approval_notes' => $data->approvalNotes,
                ],
            );

            return $requisition->refresh()->load('items.inventoryItem');
        });
    }
}
