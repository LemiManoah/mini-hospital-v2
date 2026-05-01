<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Appointment;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateAppointment
{
    public function __construct(
        private RecordAuditActivity $recordAuditActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Appointment
    {
        /** @var Appointment */
        return DB::transaction(function () use ($attributes): Appointment {
            $appointment = Appointment::query()->create([
                ...$attributes,
                'facility_branch_id' => $attributes['facility_branch_id'] ?? BranchContext::getActiveBranchId(),
                'created_by' => Auth::id(),
            ]);

            $user = Auth::user();

            $this->recordAuditActivity->handle(
                logName: 'appointments',
                event: 'appointment.created',
                subject: $appointment,
                description: 'Appointment created.',
                tenantId: $appointment->tenant_id,
                branchId: $appointment->facility_branch_id,
                staffId: $user instanceof User ? $user->staffId() : null,
                newValues: [
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'clinic_id' => $appointment->clinic_id,
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
