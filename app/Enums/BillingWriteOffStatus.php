<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingWriteOffStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REVERSED => 'Reversed',
        };
    }
}
