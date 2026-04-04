<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSupplier
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Supplier $supplier, array $attributes): Supplier
    {
        return DB::transaction(function () use ($supplier, $attributes): Supplier {
            $supplier->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $supplier->refresh();
        });
    }
}
