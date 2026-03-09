<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of a patient visit.
 */
enum VisitStatus: string
{
    case SCHEDULED = 'scheduled';
    case CHECKED_IN = 'checked_in';
    case IN_TREATMENT = 'in_treatment';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';
    case REGISTERED = 'registered';
    case TRIAGED = 'triaged';
    case WAITING_CONSULTATION = 'waiting_consultation';
    case IN_CONSULTATION = 'in_consultation';
    case WAITING_LAB = 'waiting_lab';
    case WAITING_IMAGING = 'waiting_imaging';
    case WAITING_PHARMACY = 'waiting_pharmacy';
    case ADMITTED = 'admitted';
    case DISCHARGED = 'discharged';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::CHECKED_IN => 'Checked In',
            self::IN_TREATMENT => 'In Treatment',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
            self::REGISTERED => 'Registered',
            self::TRIAGED => 'Triaged',
            self::WAITING_CONSULTATION => 'Waiting for Consultation',
            self::IN_CONSULTATION => 'In Consultation',
            self::WAITING_LAB => 'Waiting for Lab',
            self::WAITING_IMAGING => 'Waiting for Imaging',
            self::WAITING_PHARMACY => 'Waiting for Pharmacy',
            self::ADMITTED => 'Admitted',
            self::DISCHARGED => 'Discharged',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED, self::REGISTERED => 'gray',
            self::CHECKED_IN, self::TRIAGED, self::WAITING_CONSULTATION, self::WAITING_LAB, self::WAITING_IMAGING, self::WAITING_PHARMACY => 'blue',
            self::IN_TREATMENT, self::IN_CONSULTATION, self::ADMITTED => 'yellow',
            self::COMPLETED, self::DISCHARGED => 'green',
            self::CANCELLED, self::NO_SHOW => 'red',
        };
    }
}
