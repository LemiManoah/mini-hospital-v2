<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types of payers for patient bills.
 */
enum PayerType: string
{
    case CASH = 'cash';
    case INSURANCE = 'insurance';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::INSURANCE => 'Insurance',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CASH => 'green',
            self::INSURANCE => 'blue',
        };
    }
}
