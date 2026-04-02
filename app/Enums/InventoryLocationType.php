<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryLocationType: string
{
    case MAIN_STORE = 'main_store';
    case PHARMACY = 'pharmacy';
    case LABORATORY = 'laboratory';
    case PROCEDURE_ROOM = 'procedure_room';
    case WARD_STORE = 'ward_store';
    case SATELLITE_STORE = 'satellite_store';
    case OTHER = 'other';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
