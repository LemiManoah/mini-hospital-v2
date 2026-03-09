<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Severity levels for allergies.
 */
enum AllergySeverity: string
{
    case MILD = 'mild';
    case MODERATE = 'moderate';
    case SEVERE = 'severe';
    case LIFE_THREATENING = 'life_threatening';

    public function label(): string
    {
        return match ($this) {
            self::MILD => 'Mild',
            self::MODERATE => 'Moderate',
            self::SEVERE => 'Severe',
            self::LIFE_THREATENING => 'Life Threatening',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MILD => 'green',
            self::MODERATE => 'yellow',
            self::SEVERE => 'orange',
            self::LIFE_THREATENING => 'red',
        };
    }
}
