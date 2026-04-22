<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Country;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uganda = $this->requireCountry('UG');
        $kenya = $this->requireCountry('KE');
        $tanzania = $this->requireCountry('TZ');
        $rwanda = $this->requireCountry('RW');
        $southAfrica = $this->requireCountry('ZA');
        $usa = $this->requireCountry('US');
        $uk = $this->requireCountry('GB');
        $southSudan = $this->requireCountry('SS');

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

        $addresses = array_merge($addresses, [
            ['city' => 'Nairobi', 'district' => 'Nairobi', 'country_id' => $kenya->id],
            ['city' => 'Mombasa', 'district' => 'Mombasa', 'country_id' => $kenya->id],
            ['city' => 'Kisumu', 'district' => 'Kisumu', 'country_id' => $kenya->id],
            ['city' => 'Nakuru', 'district' => 'Nakuru', 'country_id' => $kenya->id],
            ['city' => 'Eldoret', 'district' => 'Uasin Gishu', 'country_id' => $kenya->id],
            ['city' => 'Dar es Salaam', 'district' => 'Dar es Salaam', 'country_id' => $tanzania->id],
            ['city' => 'Dodoma', 'district' => 'Dodoma', 'country_id' => $tanzania->id],
            ['city' => 'Arusha', 'district' => 'Arusha', 'country_id' => $tanzania->id],
            ['city' => 'Mwanza', 'district' => 'Mwanza', 'country_id' => $tanzania->id],
            ['city' => 'Zanzibar City', 'district' => 'Zanzibar Urban/West', 'country_id' => $tanzania->id],
            ['city' => 'Kigali', 'district' => 'Kigali', 'country_id' => $rwanda->id],
            ['city' => 'Rubavu', 'district' => 'Rubavu', 'country_id' => $rwanda->id],
            ['city' => 'Huye', 'district' => 'Huye', 'country_id' => $rwanda->id],
            ['city' => 'Johannesburg', 'district' => 'Gauteng', 'country_id' => $southAfrica->id],
            ['city' => 'Cape Town', 'district' => 'Western Cape', 'country_id' => $southAfrica->id],
            ['city' => 'Durban', 'district' => 'KwaZulu-Natal', 'country_id' => $southAfrica->id],
            ['city' => 'Pretoria', 'district' => 'Gauteng', 'country_id' => $southAfrica->id],
            ['city' => 'New York', 'district' => 'New York', 'country_id' => $usa->id],
            ['city' => 'Los Angeles', 'district' => 'California', 'country_id' => $usa->id],
            ['city' => 'Chicago', 'district' => 'Illinois', 'country_id' => $usa->id],
            ['city' => 'Houston', 'district' => 'Texas', 'country_id' => $usa->id],
            ['city' => 'London', 'district' => 'Greater London', 'country_id' => $uk->id],
            ['city' => 'Manchester', 'district' => 'Greater Manchester', 'country_id' => $uk->id],
            ['city' => 'Birmingham', 'district' => 'West Midlands', 'country_id' => $uk->id],
            ['city' => 'Edinburgh', 'district' => 'City of Edinburgh', 'country_id' => $uk->id],
            ['city' => 'Malakia', 'district' => 'Juba', 'country_id' => $southSudan->id],
            ['city' => 'Konyo-Konyo', 'district' => 'Juba', 'country_id' => $southSudan->id],
            ['city' => 'Gudele', 'district' => 'Juba', 'country_id' => $southSudan->id],
            ['city' => 'Bor Town', 'district' => 'Bor', 'country_id' => $southSudan->id],
        ]);

        foreach ($addresses as $address) {
            Address::query()->updateOrCreate(
                ['city' => $address['city'], 'district' => $address['district']],
                $address
            );
        }
    }

    private function requireCountry(string $countryCode): Country
    {
        $country = Country::query()->where('country_code', $countryCode)->first();

        if (! $country instanceof Country) {
            $this->call(CountrySeeder::class);
            $country = Country::query()->where('country_code', $countryCode)->first();
        }

        if (! $country instanceof Country) {
            throw new RuntimeException(sprintf('Missing country [%s] for address seeding.', $countryCode));
        }

        return $country;
    }
}
