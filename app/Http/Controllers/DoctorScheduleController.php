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
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DoctorScheduleController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:doctor_schedules.view', only: ['index']),
            new Middleware('permission:doctor_schedules.create', only: ['create', 'store']),
            new Middleware('permission:doctor_schedules.update', only: ['edit', 'update']),
            new Middleware('permission:doctor_schedules.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $doctorSchedules = $this->activeBranchWorkspace->apply(DoctorSchedule::query())
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
        $validated = $request->validated();

        if (! $this->usesActiveBranchAssignments($validated)) {
            return back()->with('error', 'Select a doctor and clinic from the active branch.');
        }

        $action->handle($validated);

        return to_route('appointments.schedules.index')->with('success', 'Doctor schedule created successfully.');
    }

    public function edit(DoctorSchedule $schedule): Response
    {
        $this->activeBranchWorkspace->authorizeModel($schedule);

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
        $this->activeBranchWorkspace->authorizeModel($schedule);

        $validated = $request->validated();

        if (! $this->usesActiveBranchAssignments($validated)) {
            return back()->with('error', 'Select a doctor and clinic from the active branch.');
        }

        $action->handle($schedule, $validated);

        return to_route('appointments.schedules.index')
            ->with('success', 'Doctor schedule updated successfully.');
    }

    public function destroy(
        DeleteDoctorScheduleRequest $request,
        DoctorSchedule $schedule,
        DeleteDoctorSchedule $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($schedule);

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
                fn (Builder $query): Builder => $query->forActiveBranch()
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
            ->where('branch_id', BranchContext::getActiveBranchId())
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function usesActiveBranchAssignments(array $attributes): bool
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        if (! is_string($activeBranchId) || $activeBranchId === '') {
            return false;
        }

        $clinicId = $attributes['clinic_id'] ?? null;
        $doctorId = $attributes['doctor_id'] ?? null;

        return is_string($clinicId)
            && $clinicId !== ''
            && is_string($doctorId)
            && $doctorId !== ''
            && Clinic::query()
                ->whereKey($clinicId)
                ->where('branch_id', $activeBranchId)
                ->exists()
            && Staff::query()
                ->whereKey($doctorId)
                ->whereHas('branches', function (Builder $query) use ($activeBranchId): void {
                    $query->where('facility_branches.id', $activeBranchId);
                })
                ->exists();
    }
}
