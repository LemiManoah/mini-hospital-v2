<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

final readonly class DeleteInventoryItem
{
    public function handle(InventoryItem $inventoryItem): void
    {
        DB::transaction(fn () => $inventoryItem->delete());
    }
}
