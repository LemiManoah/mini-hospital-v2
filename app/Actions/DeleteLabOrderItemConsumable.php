<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabOrderItemConsumable;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

final readonly class DeleteLabOrderItemConsumable
{
    public function __construct(
        private SyncLabOrderItemActualCost $syncLabOrderItemActualCost,
    ) {}

    public function handle(LabOrderItemConsumable $consumable): void
    {
        DB::transaction(function () use ($consumable): void {
            $orderItem = $consumable->orderItem;

            StockMovement::query()
                ->where('source_document_type', LabOrderItemConsumable::class)
                ->where('source_document_id', $consumable->id)
                ->delete();

            $consumable->delete();

            if ($orderItem !== null) {
                $this->syncLabOrderItemActualCost->handle($orderItem);
            }
        });
    }
}
