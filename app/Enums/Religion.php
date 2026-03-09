<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient religion.
 */
enum Religion: string
{
    case CHRISTIAN = 'christian';
    case MUSLIM = 'muslim';
    case HINDU = 'hindu';
    case BUDDHIST = 'buddhist';
    case OTHER = 'other';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::CHRISTIAN => 'Christian',
            self::MUSLIM => 'Muslim',
            self::HINDU => 'Hindu',
            self::BUDDHIST => 'Buddhist',
            self::OTHER => 'Other',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function color(): string
    {
        return 'gray';
    }
}
