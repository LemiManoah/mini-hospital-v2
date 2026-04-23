<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Enums\VisitStatus;
use App\Models\PatientVisit;

final readonly class SyncAppointmentStatusFromVisit
{
    public function handle(PatientVisit $visit): void
    {
        if (! $visit->status instanceof VisitStatus) {
            return;
        }

        if ($visit->appointment_id === null) {
            return;
        }

        $appointment = $visit->appointment()->first();

        if ($appointment === null) {
            return;
        }

        $status = match ($visit->status) {
            VisitStatus::REGISTERED => AppointmentStatus::CHECKED_IN,
            VisitStatus::IN_PROGRESS => AppointmentStatus::IN_PROGRESS,
            VisitStatus::AWAITING_PAYMENT => AppointmentStatus::IN_PROGRESS,
            VisitStatus::COMPLETED => AppointmentStatus::COMPLETED,
            VisitStatus::CANCELLED => AppointmentStatus::CANCELLED,
        };

        $attributes = ['status' => $status];

        if (
            $status === AppointmentStatus::COMPLETED
            && $appointment->completed_at === null
        ) {
            $attributes['completed_at'] = now();
        }

        $appointment->update($attributes);
    }
}
