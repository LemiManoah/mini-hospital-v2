<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffType: string
{
    case MEDICAL = 'medical';
    case NURSING = 'nursing';
    case ALLIED_HEALTH = 'allied_health';
    case ADMINISTRATIVE = 'administrative';
    case SUPPORT = 'support';
    case TECHNICAL = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::MEDICAL => 'Medical',
            self::NURSING => 'Nursing',
            self::ALLIED_HEALTH => 'Allied Health',
            self::ADMINISTRATIVE => 'Administrative',
            self::SUPPORT => 'Support',
            self::TECHNICAL => 'Technical',
        };
    }
}
