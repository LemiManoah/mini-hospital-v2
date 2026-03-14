<?php

declare(strict_types=1);

namespace App\Enums;

enum DrugDosageForm: string
{
    case TABLET = 'tablet';
    case CAPSULE = 'capsule';
    case SYRUP = 'syrup';
    case SUSPENSION = 'suspension';
    case INJECTION = 'injection';
    case INFUSION = 'infusion';
    case CREAM = 'cream';
    case OINTMENT = 'ointment';
    case DROPS = 'drops';
    case INHALER = 'inhaler';
    case SUPPOSITORY = 'suppository';
    case OTHER = 'other';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
