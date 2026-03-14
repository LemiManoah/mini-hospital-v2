<?php

declare(strict_types=1);

namespace App\Enums;

enum LabBillingStatus: string
{
    case PENDING = 'pending';
    case BILLED = 'billed';
    case PAID = 'paid';
    case INSURANCE = 'insurance';

    public function label(): string
    {
        return mb_convert_case($this->value, MB_CASE_TITLE);
    }
}
