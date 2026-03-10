<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Levels of healthcare facilities.
 */
enum FacilityLevel: string
{
    case HEALTH_CENTER_I = 'hc_i';
    case HEALTH_CENTER_II = 'hc_ii';
    case HEALTH_CENTER_III = 'hc_iii';
    case HEALTH_CENTER_IV = 'hc_iv';
    case CLINIC = 'clinic';
    case HOSPITAL = 'hospital';
    case REFERRAL_HOSPITAL = 'referral_hospital';

    public function label(): string
    {
        return match ($this) {
            self::HEALTH_CENTER_I => 'Health Center I',
            self::HEALTH_CENTER_II => 'Health Center II',
            self::HEALTH_CENTER_III => 'Health Center III',
            self::HEALTH_CENTER_IV => 'Health Center IV',
            self::CLINIC => 'Clinic',
            self::HOSPITAL => 'Hospital',
            self::REFERRAL_HOSPITAL => 'Referral Hospital',
        };
    }
}
