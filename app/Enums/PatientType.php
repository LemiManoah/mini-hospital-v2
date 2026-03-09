<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types of patients.
 */
enum PatientType: string
{
    case NEW = 'new';
    case RETURNING = 'returning';
    case EMERGENCY = 'emergency';
    case INPATIENT = 'inpatient';
    case OUTPATIENT = 'outpatient';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New Patient',
            self::RETURNING => 'Returning Patient',
            self::EMERGENCY => 'Emergency',
            self::INPATIENT => 'Inpatient',
            self::OUTPATIENT => 'Outpatient',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'green',
            self::RETURNING => 'blue',
            self::EMERGENCY => 'red',
            self::INPATIENT => 'purple',
            self::OUTPATIENT => 'gray',
        };
    }
}
