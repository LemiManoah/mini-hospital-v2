<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCurrency
{
    public function handle(Currency $currency): bool
    {
        return DB::transaction(function () use ($currency): bool {
            if (! $currency->modifiable) {
                return false;
            }

            return $currency->delete() === true;
        });
    }
}
