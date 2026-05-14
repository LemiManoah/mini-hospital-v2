<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

final readonly class DeleteInventoryItem
{
    public function __construct(
        private SyncInventoryItemChargeMaster $syncInventoryItemChargeMaster,
    ) {}

    public function handle(InventoryItem $inventoryItem): void
    {
        DB::transaction(function () use ($inventoryItem): void {
            $this->syncInventoryItemChargeMaster->handle($inventoryItem->forceFill([
                'is_active' => false,
            ]));

            $inventoryItem->delete();
        });
    }
}
