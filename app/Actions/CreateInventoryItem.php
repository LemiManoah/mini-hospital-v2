<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Inventory\CreateInventoryItemDTO;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateInventoryItem
{
    public function handle(CreateInventoryItemDTO $data): InventoryItem
    {
        return DB::transaction(fn (): InventoryItem => InventoryItem::query()->create([
            ...$data->toAttributes(),
            'created_by' => Auth::id(),
        ]));
    }
}
