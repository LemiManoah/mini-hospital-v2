<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Billable item categories used in insurance package pricing.
 */
enum BillableItemType: string
{
    case SERVICE = 'service';
    case DRUG = 'drug';
    case TEST = 'test';
    case IMAGING = 'imaging';
    case PROCEDURE = 'procedure';
    case BED_DAY = 'bed_day';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::DRUG => 'Drug',
            self::TEST => 'Test',
            self::IMAGING => 'Imaging',
            self::PROCEDURE => 'Procedure',
            self::BED_DAY => 'Bed Day',
            self::OTHER => 'Other',
        };
    }
}
