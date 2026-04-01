<?php

declare(strict_types=1);

namespace App\Enums;

enum LabSpecimenStatus: string
{
    case COLLECTED = 'collected';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
