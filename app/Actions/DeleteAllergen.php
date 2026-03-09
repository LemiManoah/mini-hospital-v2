<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Allergen;
use Illuminate\Support\Facades\DB;

final readonly class DeleteAllergen
{
    public function handle(Allergen $allergen): bool
    {
        return DB::transaction(fn (): bool => $allergen->delete());
    }
}
