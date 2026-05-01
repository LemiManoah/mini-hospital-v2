<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class MarkAppointmentNoShow
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $oldStatus = $appointment->status->value;

            $appointment->update([
                'status' => AppointmentStatus::NO_SHOW,
                'updated_by' => Auth::id(),
            ]);

            $appointment = $appointment->refresh();

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'appointments',
                event: 'appointment.no_show',
                subject: $appointment,
                description: 'Appointment marked as no-show.',
                tenantId: $appointment->tenant_id,
                branchId: $appointment->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                oldValues: [
                    'status' => $oldStatus,
                ],
                newValues: [
                    'status' => $appointment->status->value,
                ],
            );

            return $appointment;
        });
    }
}
