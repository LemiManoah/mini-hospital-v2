<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabRequest;
use App\Models\LabRequestItem;
use Illuminate\Support\Facades\DB;

final readonly class DeletePendingLabRequestItem
{
    public function __construct(
        private DeletePendingLabRequest $deletePendingLabRequest,
        private SyncLabRequestCharge $syncLabRequestCharge,
    ) {}

    public function handle(LabRequestItem $labRequestItem): void
    {
        /** @var LabRequest $labRequest */
        $labRequest = $labRequestItem->request()->firstOrFail();

        DB::transaction(function () use ($labRequestItem, $labRequest): void {
            $labRequestItem->delete();

            if (! $labRequest->items()->exists()) {
                $this->deletePendingLabRequest->handle($labRequest);

                return;
            }

            $labRequest->unsetRelation('items');
            $labRequest->load(['items.test', 'visit.payer']);

            $this->syncLabRequestCharge->handle($labRequest);
        });
    }
}
