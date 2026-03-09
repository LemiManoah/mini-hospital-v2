<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Allergen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateAllergen
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Allergen
    {
        return DB::transaction(function () use ($attributes): Allergen {
            return Allergen::query()->create([
                ...$attributes,
                'created_by' => Auth::id(),
            ]);
        });
    }
}
