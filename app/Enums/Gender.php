<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient gender options.
 */
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::OTHER => 'Other',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function color(): string
    {
        return 'gray';
    }
}
