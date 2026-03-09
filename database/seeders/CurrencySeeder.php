<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

final class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'Botswana Pula', 'code' => 'BWP', 'symbol' => 'P', 'modifiable' => false],
            ['name' => 'CFA Franc', 'code' => 'XOF', 'symbol' => 'CFA', 'modifiable' => false],
            ['name' => 'Egyptian Pound', 'code' => 'EGP', 'symbol' => 'EGP', 'modifiable' => false],
            ['name' => 'Ghana Cedi', 'code' => 'GHS', 'symbol' => 'GHS', 'modifiable' => false],
            ['name' => 'Kenyan Shilling', 'code' => 'KES', 'symbol' => 'KSh', 'modifiable' => false],
            ['name' => 'Malawian Kwacha', 'code' => 'MWK', 'symbol' => 'MK', 'modifiable' => false],
            ['name' => 'Mauritian Rupee', 'code' => 'MUR', 'symbol' => 'MUR', 'modifiable' => false],
            ['name' => 'Moroccan Dirham', 'code' => 'MAD', 'symbol' => 'MAD', 'modifiable' => false],
            ['name' => 'Namibian Dollar', 'code' => 'NAD', 'symbol' => 'N$', 'modifiable' => false],
            ['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => 'NGN', 'modifiable' => false],
            ['name' => 'Rwandan Franc', 'code' => 'RWF', 'symbol' => 'RWF', 'modifiable' => false],
            ['name' => 'South African Rand', 'code' => 'ZAR', 'symbol' => 'R', 'modifiable' => false],
            ['name' => 'Tanzanian Shilling', 'code' => 'TZS', 'symbol' => 'TSh', 'modifiable' => false],
            ['name' => 'Tunisian Dinar', 'code' => 'TND', 'symbol' => 'TND', 'modifiable' => false],
            ['name' => 'Ugandan Shilling', 'code' => 'UGX', 'symbol' => 'USh', 'modifiable' => false],
            ['name' => 'Zambian Kwacha', 'code' => 'ZMW', 'symbol' => 'ZK', 'modifiable' => false],
            ['name' => 'Zimbabwean Dollar', 'code' => 'ZWL', 'symbol' => 'Z$', 'modifiable' => false],
            ['name' => 'United States Dollar', 'code' => 'USD', 'symbol' => '$', 'modifiable' => false],
            ['name' => 'South Sudanese Pound', 'code' => 'SSD', 'symbol' => 'SSP', 'modifiable' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
