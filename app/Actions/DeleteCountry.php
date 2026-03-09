<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Country;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCountry
{
    public function handle(Country $country): bool
    {
        return DB::transaction(fn (): bool => $country->delete());
    }
}
