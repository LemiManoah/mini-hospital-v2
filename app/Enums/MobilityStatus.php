<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient mobility status.
 */
enum MobilityStatus: string
{
    case INDEPENDENT = 'independent';
    case ASSISTED = 'assisted';
    case WHEELCHAIR = 'wheelchair';
    case STRETCHER = 'stretcher';

    public function label(): string
    {
        return match ($this) {
            self::INDEPENDENT => 'Independent',
            self::ASSISTED => 'Assisted',
            self::WHEELCHAIR => 'Wheelchair',
            self::STRETCHER => 'Stretcher',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INDEPENDENT => 'green',
            self::ASSISTED => 'blue',
            self::WHEELCHAIR => 'yellow',
            self::STRETCHER => 'red',
        };
    }
}
