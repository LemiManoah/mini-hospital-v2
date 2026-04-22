<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeleteDoctorSchedule
{
    public function handle(DoctorSchedule $schedule): void
    {
        if (! $schedule->exists) {
            throw ValidationException::withMessages([
                'delete' => 'The selected schedule could not be loaded for deletion.',
            ]);
        }

        $validFrom = $schedule->valid_from->toDateString();
        $validTo = $schedule->valid_to?->toDateString();

        $bookedAppointmentsExist = Appointment::query()
            ->where('doctor_id', $schedule->doctor_id)
            ->where('clinic_id', $schedule->clinic_id)
            ->whereDate('appointment_date', '>=', $validFrom)
            ->when(
                $validTo !== null,
                fn (Builder $query): Builder => $query->whereDate('appointment_date', '<=', $validTo),
            )
            ->get(['appointment_date', 'start_time', 'end_time'])
            ->contains(function (Appointment $appointment) use ($schedule): bool {
                $appointmentDay = mb_strtolower(
                    CarbonImmutable::parse($appointment->appointment_date)->englishDayOfWeek,
                );

                $scheduleDay = $schedule->day_of_week->value;

                if ($appointmentDay !== $scheduleDay) {
                    return false;
                }

                if (
                    $appointment->start_time < $schedule->start_time
                    || $appointment->start_time >= $schedule->end_time
                ) {
                    return false;
                }

                if (
                    $appointment->end_time !== null
                    && $appointment->end_time > $schedule->end_time
                ) {
                    return false;
                }

                return true;
            });

        if ($bookedAppointmentsExist) {
            throw ValidationException::withMessages([
                'delete' => 'This schedule cannot be deleted because appointments have already been booked into it.',
            ]);
        }

        DB::transaction(fn () => $schedule->delete());
    }
}
