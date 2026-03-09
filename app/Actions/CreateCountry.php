<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Country;
use Illuminate\Support\Facades\DB;

final readonly class CreateCountry
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Country
    {
        return DB::transaction(fn (): Country => Country::query()->create($attributes));
    }
}
