<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\PatientVisit;

enum ConsultationType: string
{
    case NEW = 'new';
    case FOLLOW_UP = 'follow_up';
    case REVIEW = 'review';
    case OPD = 'opd';
    case EMERGENCY = 'emergency';
    case TELEMEDICINE = 'telemedicine';
    case GENERAL = 'general';

    public static function defaultForVisit(PatientVisit $visit): self
    {
        if ($visit->is_emergency || $visit->visit_type === VisitType::EMERGENCY) {
            return self::EMERGENCY;
        }

        return match ($visit->visit_type) {
            VisitType::NEW => self::NEW,
            VisitType::FOLLOW_UP => self::FOLLOW_UP,
            VisitType::TELEMEDICINE => self::TELEMEDICINE,
            VisitType::OUTPATIENT, VisitType::OPD_CONSULTATION => self::OPD,
            default => self::GENERAL,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New Consultation',
            self::FOLLOW_UP => 'Follow-up Consultation',
            self::REVIEW => 'Review Consultation',
            self::OPD => 'General OPD Consultation',
            self::EMERGENCY => 'Emergency Consultation',
            self::TELEMEDICINE => 'Telemedicine Consultation',
            self::GENERAL => 'General Consultation',
        };
    }
}
