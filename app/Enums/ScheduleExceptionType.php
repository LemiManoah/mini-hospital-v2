<?php

declare(strict_types=1);

namespace App\Enums;

enum ScheduleExceptionType: string
{
    case BLOCKED = 'blocked';
    case LEAVE = 'leave';
    case MEETING = 'meeting';
    case HOLIDAY = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::BLOCKED => 'Blocked',
            self::LEAVE => 'Leave',
            self::MEETING => 'Meeting',
            self::HOLIDAY => 'Holiday',
        };
    }
}
