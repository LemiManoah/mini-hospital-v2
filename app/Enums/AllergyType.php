<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types of allergies.
 */
enum AllergyType: string
{
    case MEDICATION = 'medication';
    case FOOD = 'food';
    case ENVIRONMENTAL = 'environmental';
    case LATEX = 'latex';
    case CONTRAST = 'contrast';

    public function label(): string
    {
        return match ($this) {
            self::MEDICATION => 'Medication',
            self::FOOD => 'Food',
            self::ENVIRONMENTAL => 'Environmental',
            self::LATEX => 'Latex',
            self::CONTRAST => 'Contrast Dye',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MEDICATION => 'red',
            self::FOOD => 'orange',
            self::ENVIRONMENTAL => 'green',
            self::LATEX => 'blue',
            self::CONTRAST => 'purple',
        };
    }
}
