<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GeneralStatus;
use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;

final class SubscriptionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Starter Package', 'users' => 2, 'price' => 2000000],
            ['name' => 'Standard Package', 'users' => 4, 'price' => 2000000],
            ['name' => 'Platinum Package', 'users' => 6, 'price' => 2000000],
            ['name' => 'Professional Package', 'users' => 8, 'price' => 2000000],
            ['name' => 'Advanced Package', 'users' => 10, 'price' => 2000000],
            ['name' => 'Ultimate Package', 'users' => 12, 'price' => 2000000],
            ['name' => 'Extreme Package', 'users' => 20, 'price' => 2000000],
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
