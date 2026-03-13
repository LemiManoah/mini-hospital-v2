<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of a patient visit.
 */
enum VisitStatus: string
{
    case REGISTERED = 'registered';
    case IN_PROGRESS = 'in_progress';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registered',
            self::IN_PROGRESS => 'In Progress',
            self::AWAITING_PAYMENT => 'Awaiting Payment',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGISTERED => 'gray',
            self::IN_PROGRESS => 'blue',
            self::AWAITING_PAYMENT => 'amber',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }
}
