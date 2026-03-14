<?php

declare(strict_types=1);

namespace App\Enums;

enum PrescriptionItemStatus: string
{
    case PENDING = 'pending';
    case DISPENSED = 'dispensed';
    case PARTIAL = 'partial';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
