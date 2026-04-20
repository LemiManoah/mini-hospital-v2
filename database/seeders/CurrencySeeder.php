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
            // African currencies
            ['name' => 'Angolan Kwanza', 'code' => 'AOA', 'symbol' => 'Kz', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Botswana Pula', 'code' => 'BWP', 'symbol' => 'P', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Burundian Franc', 'code' => 'BIF', 'symbol' => 'Fr', 'decimal_places' => 0, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'CFA Franc BCEAO', 'code' => 'XOF', 'symbol' => 'CFA', 'decimal_places' => 0, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'CFA Franc BEAC', 'code' => 'XAF', 'symbol' => 'FCFA', 'decimal_places' => 0, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Congolese Franc', 'code' => 'CDF', 'symbol' => 'FC', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Djiboutian Franc', 'code' => 'DJF', 'symbol' => 'Fdj', 'decimal_places' => 0, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Egyptian Pound', 'code' => 'EGP', 'symbol' => 'E£', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Eritrean Nakfa', 'code' => 'ERN', 'symbol' => 'Nfk', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Ethiopian Birr', 'code' => 'ETB', 'symbol' => 'Br', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Ghana Cedi', 'code' => 'GHS', 'symbol' => '₵', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Kenyan Shilling', 'code' => 'KES', 'symbol' => 'KSh', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Lesotho Loti', 'code' => 'LSL', 'symbol' => 'L', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Liberian Dollar', 'code' => 'LRD', 'symbol' => 'L$', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Libyan Dinar', 'code' => 'LYD', 'symbol' => 'LD', 'decimal_places' => 3, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Malagasy Ariary', 'code' => 'MGA', 'symbol' => 'Ar', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Malawian Kwacha', 'code' => 'MWK', 'symbol' => 'MK', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Mauritian Rupee', 'code' => 'MUR', 'symbol' => '₨', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Moroccan Dirham', 'code' => 'MAD', 'symbol' => 'د.م.', 'decimal_places' => 2, 'symbol_position' => 'after', 'modifiable' => false],
            ['name' => 'Mozambican Metical', 'code' => 'MZN', 'symbol' => 'MT', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Namibian Dollar', 'code' => 'NAD', 'symbol' => 'N$', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Rwandan Franc', 'code' => 'RWF', 'symbol' => 'Fr', 'decimal_places' => 0, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Seychellois Rupee', 'code' => 'SCR', 'symbol' => '₨', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Sierra Leonean Leone', 'code' => 'SLL', 'symbol' => 'Le', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Somali Shilling', 'code' => 'SOS', 'symbol' => 'Sh', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'South African Rand', 'code' => 'ZAR', 'symbol' => 'R', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'South Sudanese Pound', 'code' => 'SSP', 'symbol' => '£', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Sudanese Pound', 'code' => 'SDG', 'symbol' => 'ج.س.', 'decimal_places' => 2, 'symbol_position' => 'after', 'modifiable' => false],
            ['name' => 'Swazi Lilangeni', 'code' => 'SZL', 'symbol' => 'L', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Tanzanian Shilling', 'code' => 'TZS', 'symbol' => 'TSh', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Tunisian Dinar', 'code' => 'TND', 'symbol' => 'د.ت', 'decimal_places' => 3, 'symbol_position' => 'after', 'modifiable' => false],
            ['name' => 'Ugandan Shilling', 'code' => 'UGX', 'symbol' => 'USh', 'decimal_places' => 0, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Zambian Kwacha', 'code' => 'ZMW', 'symbol' => 'ZK', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Zimbabwean Dollar', 'code' => 'ZWL', 'symbol' => 'Z$', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            // International currencies
            ['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'British Pound', 'code' => 'GBP', 'symbol' => '£', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'United States Dollar', 'code' => 'USD', 'symbol' => '$', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Chinese Yuan', 'code' => 'CNY', 'symbol' => '¥', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
            ['name' => 'Indian Rupee', 'code' => 'INR', 'symbol' => '₹', 'decimal_places' => 2, 'symbol_position' => 'before', 'modifiable' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        // Remove the old SSD code if it exists (was a typo — correct ISO code is SSP)
        Currency::query()->where('code', 'SSD')->delete();
    }
}
