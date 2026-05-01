<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmAppointment
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    public function handle(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::CONFIRMED,
                'updated_by' => Auth::id(),
            ]);

            $appointment = $appointment->refresh();

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'appointments',
                event: 'appointment.confirmed',
                subject: $appointment,
                description: 'Appointment confirmed.',
                tenantId: $appointment->tenant_id,
                branchId: $appointment->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'status' => $appointment->status->value,
                ],
            );

            return $appointment;
        });
    }
}
