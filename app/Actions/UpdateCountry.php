<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Country;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCountry
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Country $country, array $attributes): Country
    {
        return DB::transaction(function () use ($country, $attributes): Country {
            $country->update($attributes);

            return $country;
        });
    }
}
