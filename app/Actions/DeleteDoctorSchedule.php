<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DeleteDoctorSchedule
{
    public function handle(DoctorSchedule $schedule): void
    {
        if (
            ! $schedule->exists
            || $schedule->doctor_id === null
            || $schedule->clinic_id === null
            || $schedule->day_of_week === null
        ) {
            throw ValidationException::withMessages([
                'delete' => 'The selected schedule could not be loaded for deletion.',
            ]);
        }

        $validFrom = $schedule->valid_from instanceof \Carbon\CarbonInterface
            ? $schedule->valid_from->toDateString()
            : ($schedule->valid_from !== null
                ? CarbonImmutable::parse((string) $schedule->valid_from)->toDateString()
                : null);

        $validTo = $schedule->valid_to instanceof \Carbon\CarbonInterface
            ? $schedule->valid_to->toDateString()
            : ($schedule->valid_to !== null
                ? CarbonImmutable::parse((string) $schedule->valid_to)->toDateString()
                : null);

        $bookedAppointmentsExist = Appointment::query()
            ->where('doctor_id', $schedule->doctor_id)
            ->where('clinic_id', $schedule->clinic_id)
            ->when(
                $validFrom !== null,
                fn ($query) => $query->whereDate('appointment_date', '>=', $validFrom),
            )
            ->when(
                $validTo !== null,
                fn ($query) => $query->whereDate('appointment_date', '<=', $validTo),
            )
            ->get(['appointment_date', 'start_time', 'end_time'])
            ->contains(function (Appointment $appointment) use ($schedule): bool {
                $appointmentDay = strtolower(
                    CarbonImmutable::parse($appointment->appointment_date)->englishDayOfWeek,
                );

                $scheduleDay = is_string($schedule->day_of_week)
                    ? $schedule->day_of_week
                    : $schedule->day_of_week->value;

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
