<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient attendance type for triage.
 */
enum AttendanceType: string
{
    case NEW = 'new';
    case RE_ATTENDANCE = 're_attendance';
    case REFERRAL = 'referral';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New Patient',
            self::RE_ATTENDANCE => 'Re-attendance',
            self::REFERRAL => 'Referral',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'blue',
            self::RE_ATTENDANCE => 'green',
            self::REFERRAL => 'orange',
        };
    }
}
