<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of a patient appointment.
 */
enum AppointmentStatus: string
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';
    case CHECKED_IN = 'checked_in';
    case IN_PROGRESS = 'in_progress';
    case RESCHEDULED = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
            self::CHECKED_IN => 'Checked In',
            self::IN_PROGRESS => 'In Progress',
            self::RESCHEDULED => 'Rescheduled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED, self::RESCHEDULED => 'gray',
            self::CONFIRMED, self::CHECKED_IN => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED, self::NO_SHOW => 'red',
        };
    }

    public function isFinalized(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::NO_SHOW]);
    }
}
