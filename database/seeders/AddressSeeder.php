<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;

final class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed 10 addresses
        Address::factory()->count(10)->create();
    }
}
