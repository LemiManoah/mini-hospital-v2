<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class RescheduleAppointment
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Appointment $appointment, array $attributes): Appointment
    {
        return DB::transaction(function () use ($appointment, $attributes): Appointment {
            $appointment->update([
                'appointment_date' => $attributes['appointment_date'],
                'start_time' => $attributes['start_time'],
                'end_time' => $attributes['end_time'] ?? null,
                'status' => AppointmentStatus::RESCHEDULED,
                'updated_by' => Auth::id(),
            ]);

            return $appointment->refresh();
        });
    }
}
