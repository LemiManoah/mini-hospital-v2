<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Triage grade levels for emergency assessment.
 */
enum TriageGrade: string
{
    case RED = 'red';
    case YELLOW = 'yellow';
    case GREEN = 'green';
    case BLACK = 'black';

    public function label(): string
    {
        return match ($this) {
            self::RED => 'Red - Emergency',
            self::YELLOW => 'Yellow - Priority',
            self::GREEN => 'Green - Routine',
            self::BLACK => 'Black - Deceased',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RED => 'red',
            self::YELLOW => 'yellow',
            self::GREEN => 'green',
            self::BLACK => 'black',
        };
    }
}
