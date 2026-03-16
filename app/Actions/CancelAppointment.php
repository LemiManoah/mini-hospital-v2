<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CancelAppointment
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Appointment $appointment, array $attributes): Appointment
    {
        return DB::transaction(function () use ($appointment, $attributes): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::CANCELLED,
                'cancellation_reason' => $attributes['cancellation_reason'] ?? null,
                'cancelled_by' => Auth::user()?->staff_id,
                'updated_by' => Auth::id(),
            ]);

            return $appointment->refresh();
        });
    }
}
