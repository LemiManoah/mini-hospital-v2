<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateUnit
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Unit
    {
        return DB::transaction(fn (): Unit => Unit::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
