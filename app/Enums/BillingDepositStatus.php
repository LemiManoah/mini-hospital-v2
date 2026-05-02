<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingDepositStatus: string
{
    case Held = 'held';
    case PartiallyApplied = 'partially_applied';
    case Applied = 'applied';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'Held',
            self::PartiallyApplied => 'Partially Applied',
            self::Applied => 'Applied',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }
}
