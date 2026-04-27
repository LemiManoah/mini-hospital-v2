<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class GenerateAppointmentScheduleReportAction
{
    /**
     * @return array{
     *     date: string,
     *     day_of_week: string,
     *     branch_name: string|null,
     *     total: int,
     *     by_status: array<string, int>,
     *     rows: Collection<int, Appointment>
     * }
     */
    public function handle(CarbonInterface $date, string $branchId, ?string $doctorId = null): array
    {
        /** @var Collection<int, Appointment> $appointments */
        $appointments = Appointment::query()
            ->with([
                'patient:id,first_name,middle_name,last_name,patient_number,phone_number',
                'doctor:id,first_name,last_name',
                'clinic:id,clinic_name',
                'category:id,name',
                'mode:id,name,is_virtual',
                'branch:id,name',
            ])
            ->where('facility_branch_id', $branchId)
            ->whereDate('appointment_date', $date)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('start_time')
            ->get();

        /** @var array<string, int> $byStatus */
        $byStatus = $appointments
            ->groupBy(fn (Appointment $a): string => $a->status->value)
            ->map(fn (Collection $g): int => $g->count())
            ->all();

        return [
            'date' => $date->format('d M Y'),
            'day_of_week' => $date->format('l'),
            'branch_name' => $appointments->first()?->branch?->name,
            'total' => $appointments->count(),
            'by_status' => $byStatus,
            'rows' => $appointments,
        ];
    }
}
