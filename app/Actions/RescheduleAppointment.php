<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class RescheduleAppointment
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Appointment $appointment, array $attributes): Appointment
    {
        return DB::transaction(function () use ($appointment, $attributes): Appointment {
            $oldValues = [
                'appointment_date' => $appointment->appointment_date,
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'status' => $appointment->status->value,
            ];

            $appointment->update([
                'appointment_date' => $attributes['appointment_date'],
                'start_time' => $attributes['start_time'],
                'end_time' => $attributes['end_time'] ?? null,
                'status' => AppointmentStatus::RESCHEDULED,
                'updated_by' => Auth::id(),
            ]);

            $appointment = $appointment->refresh();

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'appointments',
                event: 'appointment.rescheduled',
                subject: $appointment,
                description: 'Appointment rescheduled.',
                tenantId: $appointment->tenant_id,
                branchId: $appointment->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                oldValues: $oldValues,
                newValues: [
                    'appointment_date' => $appointment->appointment_date,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'status' => $appointment->status->value,
                ],
            );

            return $appointment;
        });
    }
}
