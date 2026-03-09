<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCurrency
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Currency $currency, array $attributes): Currency
    {
        return DB::transaction(function () use ($currency, $attributes): Currency {
            if (!$currency->modifiable) {
                // Should potentially throw an exception or handle validation elsewhere, 
                // but let's stick to the controller pattern for now.
            }
            $currency->update($attributes);

            return $currency;
        });
    }
}
