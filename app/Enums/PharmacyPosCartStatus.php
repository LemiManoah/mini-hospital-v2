<?php

declare(strict_types=1);

namespace App\Enums;

enum PharmacyPosCartStatus: string
{
    case Active = 'active';
    case Held = 'held';
    case Converted = 'converted';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Held => 'Held',
            self::Converted => 'Converted',
            self::Abandoned => 'Abandoned',
        };
    }
}
