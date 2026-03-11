<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Database\Seeder;

final class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            // Mass
            ['name' => 'Milligram', 'symbol' => 'mg', 'description' => 'Metric unit of mass.', 'type' => UnitType::MASS],
            ['name' => 'Gram', 'symbol' => 'g', 'description' => 'Metric unit of mass.', 'type' => UnitType::MASS],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'description' => 'Metric unit of mass.', 'type' => UnitType::MASS],
            
            // Volume
            ['name' => 'Milliliter', 'symbol' => 'ml', 'description' => 'Metric unit of volume.', 'type' => UnitType::VOLUME],
            ['name' => 'Liter', 'symbol' => 'L', 'description' => 'Metric unit of volume.', 'type' => UnitType::VOLUME],
            
            // Temperature
            ['name' => 'Celsius', 'symbol' => '°C', 'description' => 'Unit of temperature.', 'type' => UnitType::TEMPERATURE],
            ['name' => 'Fahrenheit', 'symbol' => '°F', 'description' => 'Unit of temperature.', 'type' => UnitType::TEMPERATURE],
            
            // Time
            ['name' => 'Minute', 'symbol' => 'min', 'description' => 'Unit of time.', 'type' => UnitType::TIME],
            ['name' => 'Hour', 'symbol' => 'hr', 'description' => 'Unit of time.', 'type' => UnitType::TIME],
            ['name' => 'Day', 'symbol' => 'day', 'description' => 'Unit of time.', 'type' => UnitType::TIME],
            
            // Count
            ['name' => 'Tablet', 'symbol' => 'tab', 'description' => 'Countable solid dose.', 'type' => UnitType::COUNT],
            ['name' => 'Capsule', 'symbol' => 'cap', 'description' => 'Countable solid dose.', 'type' => UnitType::COUNT],
            ['name' => 'Sachet', 'symbol' => 'sachet', 'description' => 'Countable package.', 'type' => UnitType::COUNT],
            ['name' => 'Puff', 'symbol' => 'puff', 'description' => 'Countable dose for inhalers.', 'type' => UnitType::COUNT],
        ];

        foreach ($units as $unit) {
            Unit::query()->updateOrCreate(
                ['symbol' => $unit['symbol']],
                $unit
            );
        }
    }
}
