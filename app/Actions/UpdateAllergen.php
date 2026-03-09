<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Allergen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAllergen
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Allergen $allergen, array $attributes): Allergen
    {
        return DB::transaction(function () use ($allergen, $attributes): Allergen {
            $allergen->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            return $allergen;
        });
    }
}
