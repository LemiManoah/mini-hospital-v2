<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types of units of measurement.
 */
enum UnitType: string
{
    case MASS = 'mass';
    case VOLUME = 'volume';
    case LENGTH = 'length';
    case TEMPERATURE = 'temperature';
    case TIME = 'time';
    case COUNT = 'count';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MASS => 'Mass',
            self::VOLUME => 'Volume',
            self::LENGTH => 'Length',
            self::TEMPERATURE => 'Temperature',
            self::TIME => 'Time',
            self::COUNT => 'Count',
            self::OTHER => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MASS => 'blue',
            self::VOLUME => 'cyan',
            self::LENGTH => 'green',
            self::TEMPERATURE => 'orange',
            self::TIME => 'purple',
            self::COUNT => 'indigo',
            self::OTHER => 'gray',
        };
    }
}
