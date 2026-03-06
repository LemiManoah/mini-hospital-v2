<?php

namespace App\Enums;

/**
 * Types of patient visits.
 */
enum VisitType: string
{
    case NEW = 'new';
    case FOLLOW_UP = 'follow_up';
    case EMERGENCY = 'emergency';
    case INPATIENT = 'inpatient';
    case OUTPATIENT = 'outpatient';
    case OPD_CONSULTATION = 'opd_consultation';
    case DAY_CARE = 'day_care';
    case PROCEDURE = 'procedure';
    case TELEMEDICINE = 'telemedicine';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New Visit',
            self::FOLLOW_UP => 'Follow-up',
            self::EMERGENCY => 'Emergency',
            self::INPATIENT => 'Inpatient',
            self::OUTPATIENT => 'Outpatient',
            self::OPD_CONSULTATION => 'OPD Consultation',
            self::DAY_CARE => 'Day Care',
            self::PROCEDURE => 'Procedure',
            self::TELEMEDICINE => 'Telemedicine',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW, self::OPD_CONSULTATION, self::OUTPATIENT => 'blue',
            self::FOLLOW_UP => 'green',
            self::EMERGENCY => 'red',
            self::INPATIENT, self::DAY_CARE => 'purple',
            self::PROCEDURE => 'yellow',
            self::TELEMEDICINE => 'cyan',
        };
    }
}
