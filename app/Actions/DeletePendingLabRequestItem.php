<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingLabRequestItem
{
    public function __construct(
        private DeletePendingLabRequest $deletePendingLabRequest,
        private SyncLabRequestCharge $syncLabRequestCharge,
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(LabRequestItem $labRequestItem): void
    {
        /** @var LabRequest $labRequest */
        $labRequest = $labRequestItem->request()->firstOrFail();
        $oldTestIds = $labRequest->items()->pluck('test_id')->all();

        DB::transaction(function () use ($labRequestItem, $labRequest, $oldTestIds): void {
            $labRequestItem->delete();

            if (! $labRequest->items()->exists()) {
                $this->deletePendingLabRequest->handle($labRequest);

                return;
            }

            $labRequest->unsetRelation('items');
            $labRequest->load(['items.test', 'visit.payer']);

            $this->syncLabRequestCharge->handle($labRequest);
            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'laboratory',
                event: 'lab_request.item_removed',
                subject: $labRequest,
                description: 'Laboratory test removed from request.',
                tenantId: $labRequest->tenant_id,
                branchId: $labRequest->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : $labRequest->requested_by,
                oldValues: [
                    'test_ids' => $oldTestIds,
                ],
                newValues: [
                    'test_ids' => $labRequest->items->pluck('test_id')->all(),
                ],
                metadata: [
                    'removed_test_id' => $labRequestItem->test_id,
                ],
            );
        });
    }
}
