<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDoctorScheduleException;
use App\Actions\DeleteDoctorScheduleException;
use App\Actions\UpdateDoctorScheduleException;
use App\Enums\ScheduleExceptionType;
use App\Http\Requests\DeleteDoctorScheduleExceptionRequest;
use App\Http\Requests\StoreDoctorScheduleExceptionRequest;
use App\Http\Requests\UpdateDoctorScheduleExceptionRequest;
use App\Models\Clinic;
use App\Models\DoctorScheduleException;
use App\Models\Staff;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DoctorScheduleExceptionController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:doctor_schedule_exceptions.view', only: ['index']),
            new Middleware('permission:doctor_schedule_exceptions.create', only: ['create', 'store']),
            new Middleware('permission:doctor_schedule_exceptions.update', only: ['edit', 'update']),
            new Middleware('permission:doctor_schedule_exceptions.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));

        $exceptions = DoctorScheduleException::query()
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
                            ->where('reason', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('type', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('doctor', static function (Builder $doctorQuery) use ($search): void {
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
            ->latest('exception_date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('appointments/exceptions/index', [
            'exceptions' => $exceptions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('appointments/exceptions/create', $this->formOptions());
    }

    public function store(
        StoreDoctorScheduleExceptionRequest $request,
        CreateDoctorScheduleException $action,
    ): RedirectResponse {
        $action->handle($request->validated());

        return to_route('appointments.exceptions.index')
            ->with('success', 'Schedule exception created successfully.');
    }

    public function edit(DoctorScheduleException $exception): Response
    {
        return Inertia::render('appointments/exceptions/edit', [
            'exception' => $exception->load([
                'doctor:id,first_name,last_name',
                'clinic:id,clinic_name',
                'branch:id,name',
            ]),
            ...$this->formOptions($exception),
        ]);
    }

    public function update(
        UpdateDoctorScheduleExceptionRequest $request,
        DoctorScheduleException $exception,
        UpdateDoctorScheduleException $action,
    ): RedirectResponse {
        $action->handle($exception, $request->validated());

        return to_route('appointments.exceptions.index')
            ->with('success', 'Schedule exception updated successfully.');
    }

    public function destroy(
        DeleteDoctorScheduleExceptionRequest $request,
        DoctorScheduleException $exception,
        DeleteDoctorScheduleException $action,
    ): RedirectResponse {
        $action->handle($exception);

        return to_route('appointments.exceptions.index')
            ->with('success', 'Schedule exception deleted successfully.');
    }

    private function formOptions(?DoctorScheduleException $exception = null): array
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

        if ($exception?->doctor()->exists() && ! $doctors->contains('id', $exception->doctor_id)) {
            $doctor = $exception->doctor()->first(['id', 'first_name', 'last_name']);

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

        if ($exception?->clinic()->exists() && $clinics->doesntContain('id', $exception->clinic_id)) {
            $clinic = $exception->clinic()->first(['id', 'clinic_name']);

            if ($clinic instanceof Clinic) {
                $clinics->prepend([
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                ]);
            }
        }

        return [
            'doctors' => $doctors->values()->all(),
            'clinics' => $clinics->values()->all(),
            'typeOptions' => collect(ScheduleExceptionType::cases())
                ->map(static fn (ScheduleExceptionType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->all(),
        ];
    }
}
