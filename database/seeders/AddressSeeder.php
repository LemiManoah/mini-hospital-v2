<?php

declare(strict_types = 1)
;

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

        if (!$uganda) {
            $this->call(CountrySeeder::class);
            $uganda = Country::query()->where('country_code', 'UG')->first();
        }

        $kenya = Country::query()->where('country_code', 'KE')->first();
        $tanzania = Country::query()->where('country_code', 'TZ')->first();
        $rwanda = Country::query()->where('country_code', 'RW')->first();
        $south_africa = Country::query()->where('country_code', 'ZA')->first();
        $usa = Country::query()->where('country_code', 'US')->first();
        $uk = Country::query()->where('country_code', 'GB')->first();
        $south_sudan = Country::query()->where('country_code', 'SS')->first();

        $addresses = [
            // Uganda
            ['city' => 'Ntinda', 'district' => 'Kampala', 'country_id' => $uganda->id],
            ['city' => 'Namugongo', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Kireka', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Bweyogerere', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Kira', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Kisasi', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Kisasi', 'district' => 'Wakiso', 'country_id' => $uganda->id],
            ['city' => 'Kiwatule', 'district' => 'Kampala', 'country_id' => $uganda->id],
            ['city' => 'Kansanga', 'district' => 'Kampala', 'country_id' => $uganda->id],
            ['city' => 'Ggaba', 'district' => 'Kampala', 'country_id' => $uganda->id],
            ['city' => 'Bwaise', 'district' => 'Kampala', 'country_id' => $uganda->id],
            ['city' => 'Ssonde', 'district' => 'Mukono', 'country_id' => $uganda->id],
            ['city' => 'Seeta', 'district' => 'Mukono', 'country_id' => $uganda->id],
            ['city' => 'Hoima', 'district' => 'Hoima', 'country_id' => $uganda->id],
            ['city' => 'Soroti', 'district' => 'Soroti', 'country_id' => $uganda->id],
            ['city' => 'Tororo', 'district' => 'Tororo', 'country_id' => $uganda->id],
            ['city' => 'Iganga Town', 'district' => 'Iganga', 'country_id' => $uganda->id],
            ['city' => 'Busia Town', 'district' => 'Busia', 'country_id' => $uganda->id],
            ['city' => 'Katete', 'district' => 'Mbarara', 'country_id' => $uganda->id],
            ['city' => 'Moroto Town', 'district' => 'Moroto', 'country_id' => $uganda->id],
        ];

        if ($kenya) {
            $addresses = array_merge($addresses, [
                ['city' => 'Nairobi', 'district' => 'Nairobi', 'country_id' => $kenya->id],
                ['city' => 'Mombasa', 'district' => 'Mombasa', 'country_id' => $kenya->id],
                ['city' => 'Kisumu', 'district' => 'Kisumu', 'country_id' => $kenya->id],
                ['city' => 'Nakuru', 'district' => 'Nakuru', 'country_id' => $kenya->id],
                ['city' => 'Eldoret', 'district' => 'Uasin Gishu', 'country_id' => $kenya->id],
            ]);
        }

        if ($tanzania) {
            $addresses = array_merge($addresses, [
                ['city' => 'Dar es Salaam', 'district' => 'Dar es Salaam', 'country_id' => $tanzania->id],
                ['city' => 'Dodoma', 'district' => 'Dodoma', 'country_id' => $tanzania->id],
                ['city' => 'Arusha', 'district' => 'Arusha', 'country_id' => $tanzania->id],
                ['city' => 'Mwanza', 'district' => 'Mwanza', 'country_id' => $tanzania->id],
                ['city' => 'Zanzibar City', 'district' => 'Zanzibar Urban/West', 'country_id' => $tanzania->id],
            ]);
        }

        if ($rwanda) {
            $addresses = array_merge($addresses, [
                ['city' => 'Kigali', 'district' => 'Kigali', 'country_id' => $rwanda->id],
                ['city' => 'Rubavu', 'district' => 'Rubavu', 'country_id' => $rwanda->id],
                ['city' => 'Huye', 'district' => 'Huye', 'country_id' => $rwanda->id],
            ]);
        }

        if ($south_africa) {
            $addresses = array_merge($addresses, [
                ['city' => 'Johannesburg', 'district' => 'Gauteng', 'country_id' => $south_africa->id],
                ['city' => 'Cape Town', 'district' => 'Western Cape', 'country_id' => $south_africa->id],
                ['city' => 'Durban', 'district' => 'KwaZulu-Natal', 'country_id' => $south_africa->id],
                ['city' => 'Pretoria', 'district' => 'Gauteng', 'country_id' => $south_africa->id],
            ]);
        }

        if ($usa) {
            $addresses = array_merge($addresses, [
                ['city' => 'New York', 'district' => 'New York', 'country_id' => $usa->id],
                ['city' => 'Los Angeles', 'district' => 'California', 'country_id' => $usa->id],
                ['city' => 'Chicago', 'district' => 'Illinois', 'country_id' => $usa->id],
                ['city' => 'Houston', 'district' => 'Texas', 'country_id' => $usa->id],
            ]);
        }

        if ($uk) {
            $addresses = array_merge($addresses, [
                ['city' => 'London', 'district' => 'Greater London', 'country_id' => $uk->id],
                ['city' => 'Manchester', 'district' => 'Greater Manchester', 'country_id' => $uk->id],
                ['city' => 'Birmingham', 'district' => 'West Midlands', 'country_id' => $uk->id],
                ['city' => 'Edinburgh', 'district' => 'City of Edinburgh', 'country_id' => $uk->id],
            ]);
        }

        if ($south_sudan) {
            $addresses = array_merge($addresses, [
                ['city' => 'Malakia', 'district' => 'Juba', 'country_id' => $south_sudan->id],
                ['city' => 'Konyo-Konyo', 'district' => 'Juba', 'country_id' => $south_sudan->id],
                ['city' => 'Gudele', 'district' => 'Juba', 'country_id' => $south_sudan->id],
                ['city' => 'Bor Town', 'district' => 'Bor', 'country_id' => $south_sudan->id],
            ]);
        }

        foreach ($addresses as $address) {
            Address::query()->updateOrCreate(
            ['city' => $address['city'], 'district' => $address['district']],
                $address
            );
        }
    }
}
