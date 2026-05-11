<?php

declare(strict_types=1);

namespace App\Enums;

enum InsuranceCopayType: string
{
    case NONE = 'none';
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::FIXED => 'Fixed amount',
            self::PERCENTAGE => 'Percentage',
        };
    }
}
