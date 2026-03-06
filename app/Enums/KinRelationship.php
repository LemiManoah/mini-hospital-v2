<?php

namespace App\Enums;

/**
 * Next of kin relationship options.
 */
enum KinRelationship: string
{
    case SPOUSE = 'spouse';
    case PARENT = 'parent';
    case CHILD = 'child';
    case SIBLING = 'sibling';
    case OTHER = 'other';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::SPOUSE => 'Spouse',
            self::PARENT => 'Parent',
            self::CHILD => 'Child',
            self::SIBLING => 'Sibling',
            self::OTHER => 'Other',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function color(): string
    {
        return 'gray';
    }
}
