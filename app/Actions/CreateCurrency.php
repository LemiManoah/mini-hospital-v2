<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;

final readonly class CreateCurrency
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Currency
    {
        return DB::transaction(function () use ($attributes): Currency {
            return Currency::query()->create($attributes);
        });
    }
}
