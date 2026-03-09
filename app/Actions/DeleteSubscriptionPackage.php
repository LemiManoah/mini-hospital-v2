<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\DB;

final readonly class DeleteSubscriptionPackage
{
    public function handle(SubscriptionPackage $package): bool
    {
        return DB::transaction(function () use ($package): bool {
            return $package->delete();
        });
    }
}
