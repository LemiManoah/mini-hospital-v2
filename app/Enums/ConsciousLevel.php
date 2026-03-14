<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient conscious level assessment.
 */
enum ConsciousLevel: string
{
    case ALERT = 'alert';
    case VOICE = 'voice';
    case PAIN = 'pain';
    case UNRESPONSIVE = 'unresponsive';

    public function label(): string
    {
        return match ($this) {
            self::ALERT => 'Alert',
            self::VOICE => 'Responds to Voice',
            self::PAIN => 'Responds to Pain',
            self::UNRESPONSIVE => 'Unresponsive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ALERT => 'green',
            self::VOICE => 'yellow',
            self::PAIN => 'orange',
            self::UNRESPONSIVE => 'red',
        };
    }
}
