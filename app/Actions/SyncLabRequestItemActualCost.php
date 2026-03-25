<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LabRequestItem;

final readonly class SyncLabRequestItemActualCost
{
    public function handle(LabRequestItem $labRequestItem): LabRequestItem
    {
        $totalActualCost = (float) $labRequestItem->consumables()->sum('line_cost');

        $labRequestItem->forceFill([
            'actual_cost' => $totalActualCost,
            'costed_at' => now(),
        ])->save();

        return $labRequestItem->refresh();
    }
}
