<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateInventoryItem
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(InventoryItem $inventoryItem, array $attributes): InventoryItem
    {
        return DB::transaction(function () use ($inventoryItem, $attributes): InventoryItem {
            $inventoryItem->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $inventoryItem->refresh();
        });
    }
}
