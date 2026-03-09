<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Country;
use Illuminate\Database\Seeder;

final class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uganda = Country::query()->where('country_code', 'UG')->first();

        if (! $uganda) {
            $this->call(CountrySeeder::class);
            $uganda = Country::query()->where('country_code', 'UG')->first();
        }

        $addresses = [
            ['city' => 'Kampala', 'district' => 'Kampala', 'country_id' => $uganda->id],
            ['city' => 'Entebbe', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Jinja', 'district' => 'Jinja', 'country_id' => $uganda->id],
            ['city' => 'Mbarara', 'district' => 'Mbarara', 'country_id' => $uganda->id],
            ['city' => 'Gulu', 'district' => 'Gulu', 'country_id' => $uganda->id],
            ['city' => 'Mbale', 'district' => 'Mbale', 'country_id' => $uganda->id],
            ['city' => 'Fort Portal', 'district' => 'Kabarole', 'country_id' => $uganda->id],
            ['city' => 'Arua', 'district' => 'Arua', 'country_id' => $uganda->id],
            ['city' => 'Lira', 'district' => 'Lira', 'country_id' => $uganda->id],
            ['city' => 'Masaka', 'district' => 'Masaka', 'country_id' => $uganda->id],
            ['city' => 'Mukono', 'district' => 'Mukono', 'country_id' => $uganda->id],
            ['city' => 'Wakiso', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Kabale', 'district' => 'Kabale', 'country_id' => $uganda->id],
            ['city' => 'Hoima', 'district' => 'Hoima', 'country_id' => $uganda->id],
            ['city' => 'Soroti', 'district' => 'Soroti', 'country_id' => $uganda->id],
        ];

        foreach ($addresses as $address) {
            Address::query()->updateOrCreate(
                ['city' => $address['city'], 'district' => $address['district']],
                $address
            );
        }
    }
}
