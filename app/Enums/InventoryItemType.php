<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryItemType: string
{
    case DRUG = 'drug';
    case CONSUMABLE = 'consumable';
    case SUPPLY = 'supply';
    case REAGENT = 'reagent';
    case OTHER = 'other';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }
}
