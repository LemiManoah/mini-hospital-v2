<?php

declare(strict_types=1);

namespace App\Enums;

enum DataImportStatus: string
{
    case Queued = 'queued';
    case Previewed = 'previewed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
