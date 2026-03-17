<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDoctorSchedule;
use App\Actions\DeleteDoctorSchedule;
use App\Actions\UpdateDoctorSchedule;
use App\Enums\ScheduleDay;
use App\Http\Requests\DeleteDoctorScheduleRequest;
use App\Http\Requests\StoreDoctorScheduleRequest;
use App\Http\Requests\UpdateDoctorScheduleRequest;
use App\Models\Clinic;
use App\Models\DoctorSchedule;
use App\Models\Staff;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DoctorScheduleController
{
    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $doctorSchedules = DoctorSchedule::query()
            ->with([
                'doctor:id,first_name,last_name',
                'clinic:id,clinic_name',
                'branch:id,name',
            ])
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where(function (Builder $innerQuery) use ($search): void {
                        $innerQuery
                            ->whereHas('doctor', static function (Builder $doctorQuery) use ($search): void {
                                $doctorQuery
                                    ->where('first_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search));
                            })
                            ->orWhereHas('clinic', static function (Builder $clinicQuery) use ($search): void {
                                $clinicQuery->where('clinic_name', 'like', sprintf('%%%s%%', $search));
                            });
                    });
                }
            )
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('appointments/schedules/index', [
            'doctorSchedules' => $doctorSchedules,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('appointments/schedules/create', $this->formOptions());
    }

    public function store(StoreDoctorScheduleRequest $request, CreateDoctorSchedule $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('appointments.schedules.index')->with('success', 'Doctor schedule created successfully.');
    }

    public function edit(DoctorSchedule $schedule): Response
    {
        return Inertia::render('appointments/schedules/edit', [
            'doctorSchedule' => $schedule->load([
                'doctor:id,first_name,last_name',
                'clinic:id,clinic_name',
                'branch:id,name',
            ]),
            ...$this->formOptions($schedule),
        ]);
    }

    public function update(
        UpdateDoctorScheduleRequest $request,
        DoctorSchedule $schedule,
        UpdateDoctorSchedule $action,
    ): RedirectResponse {
        $action->handle($schedule, $request->validated());

        return to_route('appointments.schedules.index')
            ->with('success', 'Doctor schedule updated successfully.');
    }

    public function destroy(
        DeleteDoctorScheduleRequest $request,
        DoctorSchedule $schedule,
        DeleteDoctorSchedule $action,
    ): RedirectResponse {
        $action->handle($schedule);

        return to_route('appointments.schedules.index')
            ->with('success', 'Doctor schedule deleted successfully.');
    }

    private function formOptions(?DoctorSchedule $schedule = null): array
    {
        $doctors = Staff::query()
            ->doctors()
            ->when(
                BranchContext::getActiveBranchId() !== null,
                fn ($query) => $query->forActiveBranch()
            )
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(static fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name)),
            ]);

        if ($schedule?->doctor()->exists() && ! $doctors->contains('id', $schedule->doctor_id)) {
            $doctor = $schedule->doctor()->first(['id', 'first_name', 'last_name']);

            if ($doctor instanceof Staff) {
                $doctors->prepend([
                    'id' => $doctor->id,
                    'name' => mb_trim(sprintf('%s %s', $doctor->first_name, $doctor->last_name)),
                ]);
            }
        }

        $clinics = Clinic::query()
            ->orderBy('clinic_name')
            ->get(['id', 'clinic_name'])
            ->map(static fn (Clinic $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->clinic_name,
            ]);

        if ($schedule?->clinic()->exists() && $clinics->doesntContain('id', $schedule->clinic_id)) {
            $clinic = $schedule->clinic()->first(['id', 'clinic_name']);

            if ($clinic instanceof Clinic) {
                $clinics->prepend([
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                ]);
            }
        }

        return [
            'dayOptions' => collect(ScheduleDay::cases())
                ->map(static fn (ScheduleDay $day): array => [
                    'value' => $day->value,
                    'label' => $day->label(),
                ])
                ->all(),
            'doctors' => $doctors->values()->all(),
            'clinics' => $clinics->values()->all(),
        ];
    }
}
