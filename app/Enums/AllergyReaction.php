<?php

namespace App\Enums;

/**
 * Typical allergy reactions.
 */
enum AllergyReaction: string
{
    case RASH = 'rash';
    case ANAPHYLAXIS = 'anaphylaxis';
    case BREATHING_DIFFICULTY = 'breathing_difficulty';
    case ITCHING = 'itching';
    case SWELLING = 'swelling';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::RASH => 'Rash',
            self::ANAPHYLAXIS => 'Anaphylaxis',
            self::BREATHING_DIFFICULTY => 'Breathing Difficulty',
            self::ITCHING => 'Itching',
            self::SWELLING => 'Swelling',
            self::OTHER => 'Other',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ANAPHYLAXIS, self::BREATHING_DIFFICULTY => 'red',
            self::SWELLING => 'orange',
            self::RASH, self::ITCHING => 'yellow',
            self::OTHER => 'gray',
        };
    }
}
