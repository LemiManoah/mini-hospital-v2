<?php

declare(strict_types = 1)
;

namespace Database\Seeders;

use App\Enums\GeneralStatus;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;

final class SubscriptionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Starter Package', 'users' => 2, 'price' => 1000000],
            ['name' => 'Standard Package', 'users' => 4, 'price' => 2000000],
            ['name' => 'Platinum Package', 'users' => 6, 'price' => 3000000],
            ['name' => 'Professional Package', 'users' => 8, 'price' => 4000000],
            ['name' => 'Advanced Package', 'users' => 10, 'price' => 5000000],
            ['name' => 'Ultimate Package', 'users' => 12, 'price' => 6000000],
            ['name' => 'Extreme Package', 'users' => 20, 'price' => 10000000],
            ['name' => 'Enterprise Package', 'users' => 50, 'price' => 20000000],
            ['name' => 'Unlimited Package', 'users' => 100, 'price' => 50000000],
        ];

        foreach ($packages as $package) {
            SubscriptionPackage::query()->updateOrCreate(
            ['name' => $package['name']],
            [
                ...$package,
                'status' => GeneralStatus::ACTIVE,
            ]
            );
        }
    }
}
