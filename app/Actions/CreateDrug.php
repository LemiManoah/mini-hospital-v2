<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Drug;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateDrug
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Drug
    {
        return DB::transaction(fn (): Drug => Drug::query()->create([
            ...$attributes,
            'created_by' => Auth::id(),
        ]));
    }
}
