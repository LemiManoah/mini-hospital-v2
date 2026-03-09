<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of billing/invoices.
 */
enum BillingStatus: string
{
    case PENDING = 'pending';
    case PARTIAL_PAID = 'partial_paid';
    case FULLY_PAID = 'fully_paid';
    case INSURANCE_PENDING = 'insurance_pending';
    case WAIVED = 'waived';
    case REFUNDED = 'refunded';
    case WRITTEN_OFF = 'written_off';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PARTIAL_PAID => 'Partially Paid',
            self::FULLY_PAID => 'Fully Paid',
            self::INSURANCE_PENDING => 'Pending Insurance',
            self::WAIVED => 'Waived',
            self::REFUNDED => 'Refunded',
            self::WRITTEN_OFF => 'Written Off',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING, self::INSURANCE_PENDING => 'yellow',
            self::PARTIAL_PAID => 'blue',
            self::FULLY_PAID => 'green',
            self::WAIVED, self::WRITTEN_OFF => 'gray',
            self::REFUNDED => 'purple',
        };
    }
    
    public function isSettled(): bool
    {
        return in_array($this, [self::FULLY_PAID, self::WAIVED, self::WRITTEN_OFF]);
    }
}
