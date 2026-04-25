<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantSupportStatus: string
{
    case STABLE = 'stable';
    case FOLLOW_UP = 'follow_up';
    case AWAITING_FACILITY = 'awaiting_facility';
    case ESCALATED = 'escalated';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::STABLE => 'Stable',
            self::FOLLOW_UP => 'Needs Follow-Up',
            self::AWAITING_FACILITY => 'Awaiting Facility',
            self::ESCALATED => 'Escalated',
            self::RESOLVED => 'Resolved',
        };
    }

    public function needsAttention(): bool
    {
        return in_array($this, [self::FOLLOW_UP, self::AWAITING_FACILITY, self::ESCALATED], true);
    }
}
