<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CancelAppointment
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
            $oldStatus = $appointment->status->value;

            $appointment->update([
                'status' => AppointmentStatus::CANCELLED,
                'cancellation_reason' => $attributes['cancellation_reason'] ?? null,
                'cancelled_by' => Auth::user()?->staff_id,
                'updated_by' => Auth::id(),
            ]);

            $appointment = $appointment->refresh();

            $user = Auth::user();
            $reason = is_string($attributes['cancellation_reason'] ?? null)
                ? $attributes['cancellation_reason']
                : null;

            $this->recordAuditActivity->handle(
                logName: 'appointments',
                event: 'appointment.cancelled',
                subject: $appointment,
                description: 'Appointment cancelled.',
                tenantId: $appointment->tenant_id,
                branchId: $appointment->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                reason: $reason,
                oldValues: [
                    'status' => $oldStatus,
                ],
                newValues: [
                    'status' => $appointment->status->value,
                    'cancellation_reason' => $appointment->cancellation_reason,
                ],
            );

            return $appointment;
        });
    }
}
