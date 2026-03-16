<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmAppointment
{
    public function handle(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment->update([
                'status' => AppointmentStatus::CONFIRMED,
                'updated_by' => Auth::id(),
            ]);

            return $appointment->refresh();
        });
    }
}
