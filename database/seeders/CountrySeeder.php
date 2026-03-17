<?php

declare(strict_types = 1)
;

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
            ['country_name' => 'Mexico', 'country_code' => 'MX', 'dial_code' => '+52', 'currency' => 'MXN', 'currency_symbol' => '$'],
            ['country_name' => 'Argentina', 'country_code' => 'AR', 'dial_code' => '+54', 'currency' => 'ARS', 'currency_symbol' => '$'],
            ['country_name' => 'Colombia', 'country_code' => 'CO', 'dial_code' => '+57', 'currency' => 'COP', 'currency_symbol' => '$'],
            ['country_name' => 'Spain', 'country_code' => 'ES', 'dial_code' => '+34', 'currency' => 'EUR', 'currency_symbol' => '€'],
            ['country_name' => 'Italy', 'country_code' => 'IT', 'dial_code' => '+39', 'currency' => 'EUR', 'currency_symbol' => '€'],
            ['country_name' => 'Russia', 'country_code' => 'RU', 'dial_code' => '+7', 'currency' => 'RUB', 'currency_symbol' => '₽'],
            ['country_name' => 'South Korea', 'country_code' => 'KR', 'dial_code' => '+82', 'currency' => 'KRW', 'currency_symbol' => '₩'],
            ['country_name' => 'Indonesia', 'country_code' => 'ID', 'dial_code' => '+62', 'currency' => 'IDR', 'currency_symbol' => 'Rp'],
            ['country_name' => 'Egypt', 'country_code' => 'EG', 'dial_code' => '+20', 'currency' => 'EGP', 'currency_symbol' => 'E£'],
            ['country_name' => 'Morocco', 'country_code' => 'MA', 'dial_code' => '+212', 'currency' => 'MAD', 'currency_symbol' => 'MAD'],
            ['country_name' => 'Saudi Arabia', 'country_code' => 'SA', 'dial_code' => '+966', 'currency' => 'SAR', 'currency_symbol' => '﷼'],
            ['country_name' => 'Turkey', 'country_code' => 'TR', 'dial_code' => '+90', 'currency' => 'TRY', 'currency_symbol' => '₺'],
            ['country_name' => 'Switzerland', 'country_code' => 'CH', 'dial_code' => '+41', 'currency' => 'CHF', 'currency_symbol' => 'CHF'],
            ['country_name' => 'Netherlands', 'country_code' => 'NL', 'dial_code' => '+31', 'currency' => 'EUR', 'currency_symbol' => '€'],
            ['country_name' => 'Sweden', 'country_code' => 'SE', 'dial_code' => '+46', 'currency' => 'SEK', 'currency_symbol' => 'kr'],
            ['country_name' => 'Norway', 'country_code' => 'NO', 'dial_code' => '+47', 'currency' => 'NOK', 'currency_symbol' => 'kr'],
            ['country_name' => 'Chile', 'country_code' => 'CL', 'dial_code' => '+56', 'currency' => 'CLP', 'currency_symbol' => '$'],
            ['country_name' => 'Peru', 'country_code' => 'PE', 'dial_code' => '+51', 'currency' => 'PEN', 'currency_symbol' => 'S/'],
            ['country_name' => 'Malaysia', 'country_code' => 'MY', 'dial_code' => '+60', 'currency' => 'MYR', 'currency_symbol' => 'RM'],
            ['country_name' => 'Singapore', 'country_code' => 'SG', 'dial_code' => '+65', 'currency' => 'SGD', 'currency_symbol' => '$'],
            ['country_name' => 'Thailand', 'country_code' => 'TH', 'dial_code' => '+66', 'currency' => 'THB', 'currency_symbol' => '฿'],
            ['country_name' => 'Vietnam', 'country_code' => 'VN', 'dial_code' => '+84', 'currency' => 'VND', 'currency_symbol' => '₫'],
            ['country_name' => 'Philippines', 'country_code' => 'PH', 'dial_code' => '+63', 'currency' => 'PHP', 'currency_symbol' => '₱'],
            ['country_name' => 'New Zealand', 'country_code' => 'NZ', 'dial_code' => '+64', 'currency' => 'NZD', 'currency_symbol' => '$'],
            ['country_name' => 'Israel', 'country_code' => 'IL', 'dial_code' => '+972', 'currency' => 'ILS', 'currency_symbol' => '₪'],
            ['country_name' => 'Pakistan', 'country_code' => 'PK', 'dial_code' => '+92', 'currency' => 'PKR', 'currency_symbol' => '₨'],
            ['country_name' => 'Bangladesh', 'country_code' => 'BD', 'dial_code' => '+880', 'currency' => 'BDT', 'currency_symbol' => '৳'],
            ['country_name' => 'Ghana', 'country_code' => 'GH', 'dial_code' => '+233', 'currency' => 'GHS', 'currency_symbol' => 'GH₵'],
            ['country_name' => 'Zambia', 'country_code' => 'ZM', 'dial_code' => '+260', 'currency' => 'ZMW', 'currency_symbol' => 'ZK'],
            ['country_name' => 'Zimbabwe', 'country_code' => 'ZW', 'dial_code' => '+263', 'currency' => 'ZWL', 'currency_symbol' => 'Z$'],
            ['country_name' => 'Ethiopia', 'country_code' => 'ET', 'dial_code' => '+251', 'currency' => 'ETB', 'currency_symbol' => 'Br'],
            ['country_name' => 'Democratic Republic of the Congo', 'country_code' => 'CD', 'dial_code' => '+243', 'currency' => 'CDF', 'currency_symbol' => 'FC'],
        ];

        foreach ($countries as $country) {
            Country::query()->updateOrCreate(
            ['country_code' => $country['country_code']],
                $country
            );
        }
    }
}
