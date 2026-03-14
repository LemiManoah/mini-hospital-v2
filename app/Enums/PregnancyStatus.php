<?php

declare(strict_types=1);

namespace App\Enums;

enum PregnancyStatus: string
{
    case UNKNOWN = 'unknown';
    case NOT_PREGNANT = 'not_pregnant';
    case PREGNANT = 'pregnant';
    case POSSIBLE = 'possible';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
