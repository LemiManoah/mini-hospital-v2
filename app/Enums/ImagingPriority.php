<?php

declare(strict_types=1);

namespace App\Enums;

enum ImagingPriority: string
{
    case ROUTINE = 'routine';
    case URGENT = 'urgent';
    case STAT = 'stat';

    public function label(): string
    {
        return match ($this) {
            self::ROUTINE => 'Routine',
            self::URGENT => 'Urgent',
            self::STAT => 'STAT (Immediate)',
        };
    }
}
