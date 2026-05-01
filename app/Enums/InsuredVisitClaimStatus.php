<?php

declare(strict_types=1);

namespace App\Enums;

enum InsuredVisitClaimStatus: string
{
    case OPEN = 'open';
    case READY_FOR_INVOICE = 'ready_for_invoice';
    case INVOICED = 'invoiced';
    case SUBMITTED = 'submitted';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case REJECTED = 'rejected';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::READY_FOR_INVOICE => 'Ready for Invoice',
            self::INVOICED => 'Invoiced',
            self::SUBMITTED => 'Submitted',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::REJECTED => 'Rejected',
            self::DISPUTED => 'Disputed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canSyncClaimAmount(): bool
    {
        return in_array($this, [
            self::OPEN,
            self::READY_FOR_INVOICE,
        ], true);
    }
}
