<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\CreateInventoryItemDTO;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateInventoryItem
{
    public function __construct(
        private SyncInventoryItemChargeMaster $syncInventoryItemChargeMaster,
    ) {}

    public function handle(CreateInventoryItemDTO $data): InventoryItem
    {
        return DB::transaction(function () use ($data): InventoryItem {
            $inventoryItem = InventoryItem::query()->create([
                ...$data->toAttributes(),
                'created_by' => Auth::id(),
            ]);

            $this->syncInventoryItemChargeMaster->handle($inventoryItem);

            return $inventoryItem->refresh();
        });
    }
}
