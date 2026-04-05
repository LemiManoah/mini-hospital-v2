<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryRequisitionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case PartiallyIssued = 'partially_issued';
    case Fulfilled = 'fulfilled';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::PartiallyIssued => 'Partially Issued',
            self::Fulfilled => 'Fulfilled',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
