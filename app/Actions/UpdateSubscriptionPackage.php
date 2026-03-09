<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSubscriptionPackage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(SubscriptionPackage $package, array $attributes): SubscriptionPackage
    {
        return DB::transaction(function () use ($package, $attributes): SubscriptionPackage {
            $package->update($attributes);

            return $package;
        });
    }
}
