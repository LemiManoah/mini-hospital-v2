<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryLocation;
use Illuminate\Support\Facades\DB;

final readonly class DeleteInventoryLocation
{
    public function handle(InventoryLocation $inventoryLocation): void
    {
        DB::transaction(fn () => $inventoryLocation->delete());
    }
}
