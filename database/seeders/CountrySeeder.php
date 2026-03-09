<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

final class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['country_name' => 'Uganda', 'country_code' => 'UG', 'dial_code' => '+256', 'currency' => 'UGX', 'currency_symbol' => 'USh'],
            ['country_name' => 'Kenya', 'country_code' => 'KE', 'dial_code' => '+254', 'currency' => 'KES', 'currency_symbol' => 'KSh'],
            ['country_name' => 'Tanzania', 'country_code' => 'TZ', 'dial_code' => '+255', 'currency' => 'TZS', 'currency_symbol' => 'TSh'],
            ['country_name' => 'Rwanda', 'country_code' => 'RW', 'dial_code' => '+250', 'currency' => 'RWF', 'currency_symbol' => 'RWF'],
            ['country_name' => 'South Sudan', 'country_code' => 'SS', 'dial_code' => '+211', 'currency' => 'SSD', 'currency_symbol' => 'SSP'],
            ['country_name' => 'Nigeria', 'country_code' => 'NG', 'dial_code' => '+234', 'currency' => 'NGN', 'currency_symbol' => 'NGN'],
            ['country_name' => 'South Africa', 'country_code' => 'ZA', 'dial_code' => '+27', 'currency' => 'ZAR', 'currency_symbol' => 'R'],
            ['country_name' => 'United States', 'country_code' => 'US', 'dial_code' => '+1', 'currency' => 'USD', 'currency_symbol' => '$'],
            ['country_name' => 'United Kingdom', 'country_code' => 'GB', 'dial_code' => '+44', 'currency' => 'GBP', 'currency_symbol' => '£'],
            ['country_name' => 'Canada', 'country_code' => 'CA', 'dial_code' => '+1', 'currency' => 'CAD', 'currency_symbol' => '$'],
            ['country_name' => 'Australia', 'country_code' => 'AU', 'dial_code' => '+61', 'currency' => 'AUD', 'currency_symbol' => '$'],
            ['country_name' => 'India', 'country_code' => 'IN', 'dial_code' => '+91', 'currency' => 'INR', 'currency_symbol' => '₹'],
            ['country_name' => 'Germany', 'country_code' => 'DE', 'dial_code' => '+49', 'currency' => 'EUR', 'currency_symbol' => '€'],
            ['country_name' => 'France', 'country_code' => 'FR', 'dial_code' => '+33', 'currency' => 'EUR', 'currency_symbol' => '€'],
            ['country_name' => 'United Arab Emirates', 'country_code' => 'AE', 'dial_code' => '+971', 'currency' => 'AED', 'currency_symbol' => 'د.إ'],
            ['country_name' => 'China', 'country_code' => 'CN', 'dial_code' => '+86', 'currency' => 'CNY', 'currency_symbol' => '¥'],
            ['country_name' => 'Japan', 'country_code' => 'JP', 'dial_code' => '+81', 'currency' => 'JPY', 'currency_symbol' => '¥'],
            ['country_name' => 'Brazil', 'country_code' => 'BR', 'dial_code' => '+55', 'currency' => 'BRL', 'currency_symbol' => 'R$'],
        ];

        foreach ($countries as $country) {
            Country::query()->updateOrCreate(
                ['country_code' => $country['country_code']],
                $country
            );
        }
    }
}
