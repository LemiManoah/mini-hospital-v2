<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\VisitStatus;
use App\Models\PatientVisit;

final readonly class TransitionPatientVisitStatus
{
    public function __construct(
        private SyncAppointmentStatusFromVisit $syncAppointmentStatusFromVisit,
    ) {}

    public function handle(PatientVisit $visit, VisitStatus $status): PatientVisit
    {
        $attributes = ['status' => $status];

        if ($status === VisitStatus::IN_PROGRESS && $visit->started_at === null) {
            $attributes['started_at'] = now();
        }

        if ($status === VisitStatus::COMPLETED && $visit->completed_at === null) {
            $attributes['completed_at'] = now();
        }

        $visit->update($attributes);

        $visit = $visit->refresh();

        $this->syncAppointmentStatusFromVisit->handle($visit);

        return $visit;
    }
}
