<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\DB;

final readonly class CreateSubscriptionPackage
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): SubscriptionPackage
    {
        return DB::transaction(fn (): SubscriptionPackage => SubscriptionPackage::query()->create($attributes));
    }
}
