<?php

declare(strict_types=1);

namespace App\Enums;

enum DispensingRecordStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
