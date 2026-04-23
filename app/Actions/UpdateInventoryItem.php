<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\UpdateInventoryItemDTO;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateInventoryItem
{
    public function handle(InventoryItem $inventoryItem, UpdateInventoryItemDTO $data): InventoryItem
    {
        return DB::transaction(function () use ($inventoryItem, $data): InventoryItem {
            $inventoryItem->update([
                ...$data->toAttributes(),
                'updated_by' => Auth::id(),
            ]);

            return $inventoryItem->refresh();
        });
    }
}
