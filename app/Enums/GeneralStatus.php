<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient marital status.
 */
enum GeneralStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::PENDING => 'Pending',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'yellow',
            self::CANCELLED => 'red',
            self::PENDING => 'blue',
        };
    }
}
