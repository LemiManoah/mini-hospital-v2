<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateInventoryItem
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): InventoryItem
    {
        return DB::transaction(fn (): InventoryItem => InventoryItem::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
