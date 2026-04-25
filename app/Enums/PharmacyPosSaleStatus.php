<?php

declare(strict_types=1);

namespace App\Enums;

enum PharmacyPosSaleStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Voided = 'voided';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Completed => 'Completed',
            self::Voided => 'Voided',
            self::Refunded => 'Refunded',
        };
    }
}
