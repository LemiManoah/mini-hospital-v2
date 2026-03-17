<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DoctorSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

final readonly class ValidatesAppointmentScheduling
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function validate(Validator $validator, array $attributes): void
    {
        $appointmentDate = $attributes['appointment_date'] ?? null;
        $startTime = $attributes['start_time'] ?? null;

        if (! is_string($appointmentDate) || ! is_string($startTime)) {
            return;
        }

        $date = CarbonImmutable::parse($appointmentDate);
        $today = CarbonImmutable::today();

        if ($date->isBefore($today)) {
            $validator->errors()->add(
                'appointment_date',
                'Appointment date cannot be in the past.',
            );

            return;
        }

        $startDateTime = CarbonImmutable::parse(
            sprintf('%s %s', $date->toDateString(), $startTime),
        );

        if ($startDateTime->isBefore(CarbonImmutable::now())) {
            $validator->errors()->add(
                'start_time',
                'Appointment time cannot be in the past.',
            );
        }

        $doctorId = $attributes['doctor_id'] ?? null;

        if (! is_string($doctorId) || $doctorId === '') {
            return;
        }

        $scheduleQuery = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', strtolower($date->englishDayOfWeek))
            ->whereDate('valid_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query
                    ->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $date->toDateString());
            })
            ->where('is_active', true);

        $branchId = BranchContext::getActiveBranchId();

        if ($branchId !== null) {
            $scheduleQuery->where(function ($query) use ($branchId): void {
                $query
                    ->where('facility_branch_id', $branchId)
                    ->orWhereNull('facility_branch_id');
            });
        }

        $schedules = $scheduleQuery->get([
            'clinic_id',
            'start_time',
            'end_time',
        ]);

        if ($schedules->isEmpty()) {
            $validator->errors()->add(
                'start_time',
                sprintf(
                    'This doctor has no active schedule on %s.',
                    $date->isoFormat('dddd, D MMMM YYYY'),
                ),
            );

            return;
        }

        $clinicId = $attributes['clinic_id'] ?? null;
        $clinicSchedules = $schedules;

        if (is_string($clinicId) && $clinicId !== '') {
            $clinicSchedules = $schedules->where('clinic_id', $clinicId)->values();

            if ($clinicSchedules->isEmpty()) {
                $validator->errors()->add(
                    'clinic_id',
                    'The selected clinic is not part of this doctor schedule for that day.',
                );

                return;
            }
        }

        $startAt = $this->normaliseTime($startTime);
        $endTime = $attributes['end_time'] ?? null;
        $endAt = is_string($endTime) && $endTime !== ''
            ? $this->normaliseTime($endTime)
            : null;

        $scheduleExists = $clinicSchedules
            ->contains(function (DoctorSchedule $schedule) use ($startAt, $endAt): bool {
                $scheduleStart = $this->normaliseTime((string) $schedule->start_time);
                $scheduleEnd = $this->normaliseTime((string) $schedule->end_time);

                if ($startAt < $scheduleStart || $startAt >= $scheduleEnd) {
                    return false;
                }

                if ($endAt !== null && $endAt > $scheduleEnd) {
                    return false;
                }

                return true;
            });

        if (! $scheduleExists) {
            $validator->errors()->add(
                'start_time',
                sprintf(
                    'This appointment falls outside the doctor schedule. Available time window%s: %s.',
                    $clinicSchedules->count() > 1 ? 's' : '',
                    $this->formatScheduleWindows($clinicSchedules),
                ),
            );
        }
    }

    /**
     * @param  Collection<int, DoctorSchedule>  $schedules
     */
    private function formatScheduleWindows(Collection $schedules): string
    {
        return $schedules
            ->map(static function (DoctorSchedule $schedule): string {
                $start = self::displayTime((string) $schedule->start_time);
                $end = self::displayTime((string) $schedule->end_time);

                return sprintf('%s-%s', $start, $end);
            })
            ->unique()
            ->implode(', ');
    }

    private function normaliseTime(string $time): string
    {
        foreach (['H:i:s', 'H:i'] as $format) {
            $parsed = CarbonImmutable::createFromFormat($format, $time);

            if ($parsed !== false) {
                return $parsed->format('H:i:s');
            }
        }

        return CarbonImmutable::parse($time)->format('H:i:s');
    }

    private static function displayTime(string $time): string
    {
        foreach (['H:i:s', 'H:i'] as $format) {
            $parsed = CarbonImmutable::createFromFormat($format, $time);

            if ($parsed !== false) {
                return $parsed->format('g:i A');
            }
        }

        return CarbonImmutable::parse($time)->format('g:i A');
    }
}
