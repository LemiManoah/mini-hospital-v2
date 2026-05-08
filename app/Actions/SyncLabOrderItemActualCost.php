<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabOrderItem;

final readonly class SyncLabOrderItemActualCost
{
    public function handle(LabOrderItem $labOrderItem): LabOrderItem
    {
        $totalActualCost = (float) $labOrderItem->consumables()->sum('line_cost');

        $labOrderItem->forceFill([
            'actual_cost' => $totalActualCost,
            'costed_at' => now(),
        ])->save();

        return $labOrderItem->refresh();
    }
}
