<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DoctorSchedule;
use App\Models\DoctorScheduleException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
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
            ->where('day_of_week', mb_strtolower($date->englishDayOfWeek))
            ->whereDate('valid_from', '<=', $date->toDateString())
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $date->toDateString());
            })
            ->where('is_active', true);

        $branchId = BranchContext::getActiveBranchId();

        if ($branchId !== null) {
            $scheduleQuery->where(function (Builder $query) use ($branchId): void {
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

            return;
        }

        $matchingExceptions = DoctorScheduleException::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('exception_date', $date->toDateString())
            ->when(
                $branchId !== null,
                static function (Builder $query) use ($branchId): void {
                    $query->where(function (Builder $innerQuery) use ($branchId): void {
                        $innerQuery
                            ->where('facility_branch_id', $branchId)
                            ->orWhereNull('facility_branch_id');
                    });
                },
            )
            ->when(
                is_string($clinicId) && $clinicId !== '',
                static function (Builder $query) use ($clinicId): void {
                    $query->where(function (Builder $innerQuery) use ($clinicId): void {
                        $innerQuery
                            ->where('clinic_id', $clinicId)
                            ->orWhereNull('clinic_id');
                    });
                },
                static function (Builder $query): void {
                    $query->whereNull('clinic_id');
                },
            )
            ->get([
                'type',
                'reason',
                'is_all_day',
                'start_time',
                'end_time',
            ]);

        if ($matchingExceptions->isEmpty()) {
            return;
        }

        $blockedException = $matchingExceptions->first(function (DoctorScheduleException $exception) use ($startAt, $endAt): bool {
            if ($exception->is_all_day) {
                return true;
            }

            if ($exception->start_time === null || $exception->end_time === null) {
                return true;
            }

            $exceptionStart = $this->normaliseTime((string) $exception->start_time);
            $exceptionEnd = $this->normaliseTime((string) $exception->end_time);
            $appointmentEnd = $endAt ?? $startAt;

            return $startAt < $exceptionEnd && $appointmentEnd > $exceptionStart;
        });

        if ($blockedException instanceof DoctorScheduleException) {
            $typeLabel = $blockedException->type?->label() ?? 'schedule exception';
            $reasonSuffix = $blockedException->reason !== null && $blockedException->reason !== ''
                ? sprintf(' Reason: %s.', $blockedException->reason)
                : '';

            $validator->errors()->add(
                'start_time',
                sprintf(
                    'This appointment falls within a doctor %s on %s.%s',
                    mb_strtolower($typeLabel),
                    $date->isoFormat('dddd, D MMMM YYYY'),
                    $reasonSuffix,
                ),
            );
        }
    }

    private static function displayTime(string $time): string
    {
        return CarbonImmutable::parse(self::safeNormalisedTime($time))->format('g:i A');
    }

    private static function safeNormalisedTime(string $time): string
    {
        $trimmed = mb_trim($time);

        if (preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::(?<second>\d{2}))?(?:\.\d+)?$/', $trimmed, $matches) === 1) {
            return sprintf(
                '%s:%s:%s',
                $matches['hour'],
                $matches['minute'],
                $matches['second'] ?? '00',
            );
        }

        return CarbonImmutable::parse($trimmed)->format('H:i:s');
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
        $trimmed = mb_trim($time);

        if (preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::(?<second>\d{2}))?(?:\.\d+)?$/', $trimmed, $matches) === 1) {
            return sprintf(
                '%s:%s:%s',
                $matches['hour'],
                $matches['minute'],
                $matches['second'] ?? '00',
            );
        }

        return CarbonImmutable::parse($trimmed)->format('H:i:s');
    }
}
