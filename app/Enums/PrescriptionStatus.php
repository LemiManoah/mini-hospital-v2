<?php

declare(strict_types=1);

namespace App\Enums;

enum PrescriptionStatus: string
{
    case PENDING = 'pending';
    case PARTIALLY_DISPENSED = 'partially_dispensed';
    case FULLY_DISPENSED = 'fully_dispensed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
