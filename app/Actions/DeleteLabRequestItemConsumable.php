<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabRequestItemConsumable;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

final readonly class DeleteLabRequestItemConsumable
{
    public function __construct(
        private SyncLabRequestItemActualCost $syncLabRequestItemActualCost,
    ) {}

    public function handle(LabRequestItemConsumable $consumable): void
    {
        DB::transaction(function () use ($consumable): void {
            $requestItem = $consumable->requestItem;

            StockMovement::query()
                ->where('source_document_type', LabRequestItemConsumable::class)
                ->where('source_document_id', $consumable->id)
                ->delete();

            $consumable->delete();

            if ($requestItem !== null) {
                $this->syncLabRequestItemActualCost->handle($requestItem);
            }
        });
    }
}
