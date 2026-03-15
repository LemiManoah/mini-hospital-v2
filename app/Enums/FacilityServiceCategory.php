<?php

declare(strict_types=1);

namespace App\Enums;

enum FacilityServiceCategory: string
{
    case DRESSING = 'dressing';
    case PHYSIOTHERAPY = 'physiotherapy';
    case PROCEDURE = 'procedure';
    case DENTAL = 'dental';
    case NURSING = 'nursing';
    case TRANSPORT = 'transport';
    case OTHER = 'other';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
