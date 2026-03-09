<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Patient marital status.
 */
enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';
    case SEPARATED = 'separated';

    public function label(): string
    {
        return match($this) {
            self::SINGLE => 'Single',
            self::MARRIED => 'Married',
            self::DIVORCED => 'Divorced',
            self::WIDOWED => 'Widowed',
            self::SEPARATED => 'Separated',
        };
    }

    public function color(): string
    {
        return 'gray';
    }
}
