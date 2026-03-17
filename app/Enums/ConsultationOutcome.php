<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Consultation outcomes.
 */
enum ConsultationOutcome: string
{
    case DISCHARGED = 'discharged';
    case ADMITTED = 'admitted';
    case REFERRED = 'referred';
    case FOLLOW_UP_REQUIRED = 'follow_up_required';
    case TRANSFERRED = 'transferred';
    case DECEASED = 'deceased';
    case LEFT_AGAINST_ADVICE = 'left_against_advice';

    public function label(): string
    {
        return match ($this) {
            self::DISCHARGED => 'Discharged',
            self::ADMITTED => 'Admitted',
            self::REFERRED => 'Referred',
            self::FOLLOW_UP_REQUIRED => 'Follow Up Required',
            self::TRANSFERRED => 'Transferred',
            self::DECEASED => 'Deceased',
            self::LEFT_AGAINST_ADVICE => 'Left Against Advice',
        };
    }
}
