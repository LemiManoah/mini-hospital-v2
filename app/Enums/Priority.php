<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Priority levels used across modules (e.g. lab requests, imaging).
 */
enum Priority: string
{
    case ROUTINE = 'routine';
    case URGENT = 'urgent';
    case STAT = 'stat';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::ROUTINE => 'Routine',
            self::URGENT => 'Urgent',
            self::STAT => 'STAT (Immediate)',
            self::CRITICAL => 'Critical',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ROUTINE => 'gray',
            self::URGENT => 'yellow',
            self::STAT => 'orange',
            self::CRITICAL => 'red',
        };
    }
    
    public function isHighPriority(): bool
    {
        return in_array($this, [self::STAT, self::CRITICAL]);
    }
}
