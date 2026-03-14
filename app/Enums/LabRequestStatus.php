<?php

declare(strict_types=1);

namespace App\Enums;

enum LabRequestStatus: string
{
    case REQUESTED = 'requested';
    case SAMPLE_COLLECTED = 'sample_collected';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
