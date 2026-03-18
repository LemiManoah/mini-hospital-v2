<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case TRIAL = 'trial';
    case PENDING_ACTIVATION = 'pending_activation';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TRIAL => 'Trial',
            self::PENDING_ACTIVATION => 'Pending Activation',
            self::ACTIVE => 'Active',
            self::PAST_DUE => 'Past Due',
            self::CANCELLED => 'Cancelled',
        };
    }
}
