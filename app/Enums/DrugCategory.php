<?php

declare(strict_types=1);

namespace App\Enums;

enum DrugCategory: string
{
    case ANALGESIC = 'analgesic';
    case ANTIBIOTIC = 'antibiotic';
    case ANTIPYRETIC = 'antipyretic';
    case ANTI_MALARIAL = 'anti_malarial';
    case ANTIHYPERTENSIVE = 'antihypertensive';
    case GASTROINTESTINAL = 'gastrointestinal';
    case RESPIRATORY = 'respiratory';
    case OTHER = 'other';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
