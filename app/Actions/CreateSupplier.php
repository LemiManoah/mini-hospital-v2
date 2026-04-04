<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateSupplier
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Supplier
    {
        return DB::transaction(fn (): Supplier => Supplier::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
