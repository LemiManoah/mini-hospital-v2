<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            CurrencySeeder::class,
            SubscriptionPackageSeeder::class,
            AllergenSeeder::class,
            AddressSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
