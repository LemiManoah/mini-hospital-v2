<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateInventoryLocation
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): InventoryLocation
    {
        return DB::transaction(fn (): InventoryLocation => InventoryLocation::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
