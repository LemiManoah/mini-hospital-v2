<?php

declare(strict_types=1);

namespace App\Enums;

enum ImagingLaterality: string
{
    case LEFT = 'left';
    case RIGHT = 'right';
    case BILATERAL = 'bilateral';
    case NOT_APPLICABLE = 'na';

    public function label(): string
    {
        return $this === self::NOT_APPLICABLE
            ? 'Not Applicable'
            : mb_convert_case($this->value, MB_CASE_TITLE);
    }
}
