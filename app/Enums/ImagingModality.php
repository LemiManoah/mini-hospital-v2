<?php

declare(strict_types=1);

namespace App\Enums;

enum ImagingModality: string
{
    case XRAY = 'xray';
    case CT = 'ct';
    case MRI = 'mri';
    case ULTRASOUND = 'ultrasound';
    case MAMMOGRAPHY = 'mammography';
    case FLUOROSCOPY = 'fluoroscopy';
    case PET_CT = 'pet_ct';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
