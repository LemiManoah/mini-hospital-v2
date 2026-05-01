<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class UpdateAppointment
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
                'doctor_id' => $appointment->doctor_id,
                'clinic_id' => $appointment->clinic_id,
                'appointment_category_id' => $appointment->appointment_category_id,
                'appointment_mode_id' => $appointment->appointment_mode_id,
                'appointment_date' => $appointment->appointment_date,
                'start_time' => $appointment->start_time,
                'end_time' => $appointment->end_time,
                'reason_for_visit' => $appointment->reason_for_visit,
                'chief_complaint' => $appointment->chief_complaint,
                'notes' => $appointment->notes,
                'status' => $appointment->status->value,
            ];

            $appointment->update([
                ...$attributes,
                'updated_by' => Auth::id(),
            ]);

            $appointment = $appointment->refresh();

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'appointments',
                event: 'appointment.updated',
                subject: $appointment,
                description: 'Appointment updated.',
                tenantId: $appointment->tenant_id,
                branchId: $appointment->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                oldValues: $oldValues,
                newValues: [
                    'doctor_id' => $appointment->doctor_id,
                    'clinic_id' => $appointment->clinic_id,
                    'appointment_category_id' => $appointment->appointment_category_id,
                    'appointment_mode_id' => $appointment->appointment_mode_id,
                    'appointment_date' => $appointment->appointment_date,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'reason_for_visit' => $appointment->reason_for_visit,
                    'chief_complaint' => $appointment->chief_complaint,
                    'notes' => $appointment->notes,
                    'status' => $appointment->status->value,
                ],
            );

            return $appointment;
        });
    }
}
