<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateUnit
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Unit $unit, array $attributes): Unit
    {
        return DB::transaction(fn (): Unit => tap($unit)->update([
            ...$attributes,
            'updated_by' => Auth::id(),
        ]));
    }
}
