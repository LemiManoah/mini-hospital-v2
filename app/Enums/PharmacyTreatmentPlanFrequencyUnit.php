<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum PharmacyTreatmentPlanFrequencyUnit: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return mb_convert_case(str_replace('_', ' ', $this->value), MB_CASE_TITLE);
    }

    public function advance(CarbonInterface $date, int $interval = 1): CarbonInterface
    {
        $interval = max(1, $interval);

        return match ($this) {
            self::DAILY => $date->copy()->addDays($interval),
            self::WEEKLY => $date->copy()->addWeeks($interval),
            self::MONTHLY => $date->copy()->addMonths($interval),
        };
    }
}
