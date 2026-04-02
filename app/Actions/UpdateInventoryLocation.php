<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\InventoryLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateInventoryLocation
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(InventoryLocation $inventoryLocation, array $attributes): InventoryLocation
    {
        return DB::transaction(function () use ($inventoryLocation, $attributes): InventoryLocation {
            $inventoryLocation->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $inventoryLocation->refresh();
        });
    }
}
