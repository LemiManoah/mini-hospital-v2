<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingLabOrderItem
{
    public function __construct(
        private DeletePendingLabOrder $deletePendingLabOrder,
        private SyncLabOrderCharge $syncLabOrderCharge,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabOrderItem $labOrderItem): void
    {
        /** @var LabOrder $labOrder */
        $labOrder = $labOrderItem->order()->firstOrFail();
        $oldTestIds = $labOrder->items()->pluck('test_id')->all();

        DB::transaction(function () use ($labOrderItem, $labOrder, $oldTestIds): void {
            $labOrderItem->delete();

            if (! $labOrder->items()->exists()) {
                $this->deletePendingLabOrder->handle($labOrder);

                return;
            }

            $labOrder->unsetRelation('items');
            $labOrder->load(['items.test', 'visit.payer']);

            $this->syncLabOrderCharge->handle($labOrder);
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_order.item_removed',
                subject: $labOrder,
                description: 'Laboratory test removed from request.',
                tenantId: $labOrder->tenant_id,
                branchId: $labOrder->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $labOrder->requested_by,
                oldValues: [
                    'test_ids' => $oldTestIds,
                ],
                newValues: [
                    'test_ids' => $labOrder->items->pluck('test_id')->all(),
                ],
                metadata: [
                    'removed_test_id' => $labOrderItem->test_id,
                ],
            );
        });
    }
}
