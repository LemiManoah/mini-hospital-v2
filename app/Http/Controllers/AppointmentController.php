<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CancelAppointment;
use App\Actions\CheckInAppointment;
use App\Actions\ConfirmAppointment;
use App\Actions\CreateAppointment;
use App\Actions\MarkAppointmentNoShow;
use App\Actions\ResolveDateRange;
use App\Actions\RescheduleAppointment;
use App\Actions\UpdateAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\PayerType;
use App\Enums\VisitType;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\CheckInAppointmentRequest;
use App\Http\Requests\ConfirmAppointmentRequest;
use App\Http\Requests\MarkAppointmentNoShowRequest;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentCategory;
use App\Models\AppointmentMode;
use App\Models\Clinic;
use App\Models\InsuranceCompany;
use App\Models\InsurancePackage;
use App\Models\Patient;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AppointmentController
{
    public function __construct(
        private ResolveDateRange $resolveDateRange,
    ) {}

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));
        $view = mb_trim((string) $request->query('view', 'list'));
        ['from' => $fromDate, 'to' => $toDate] = $this->resolveDateRange->handle(
            mb_trim((string) $request->query('from_date', '')),
            mb_trim((string) $request->query('to_date', '')),
        );
        $calendarView = $view === 'calendar' ? 'calendar' : 'list';

        $appointmentsQuery = $this->appointmentQuery()
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->whereBetween('appointment_date', [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ])
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where(function (Builder $innerQuery) use ($search): void {
                        $innerQuery
                            ->whereHas('patient', static function (Builder $patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('patient_number', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('first_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                    ->orWhere('phone_number', 'like', sprintf('%%%s%%', $search));
                            })
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
            ->orderBy('appointment_date')
            ->orderBy('start_time');

        $appointments = $calendarView === 'list'
            ? $appointmentsQuery
                ->latest('appointment_date')
                ->latest('start_time')
                ->paginate(10)
                ->withQueryString()
            : $appointmentsQuery->get();

        return Inertia::render('appointments/index', [
            'appointments' => $appointments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'view' => $calendarView,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function myAppointments(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        $status = mb_trim((string) $request->query('status', ''));
        ['from' => $fromDate, 'to' => $toDate] = $this->resolveDateRange->handle(
            mb_trim((string) $request->query('from_date', '')),
            mb_trim((string) $request->query('to_date', '')),
        );

        $appointments = $this->appointmentQuery()
            ->when(
                $user?->staff_id !== null,
                fn (Builder $query) => $query->where('doctor_id', $user?->staff_id),
            )
            ->whereBetween('appointment_date', [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ])
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('appointments/my', [
            'appointments' => $appointments,
            'filters' => [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'status' => $status,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function queue(Request $request): Response
    {
        $doctorId = mb_trim((string) $request->query('doctor_id', ''));
        $clinicId = mb_trim((string) $request->query('clinic_id', ''));
        ['from' => $fromDate, 'to' => $toDate] = $this->resolveDateRange->handle(
            mb_trim((string) $request->query('from_date', '')),
            mb_trim((string) $request->query('to_date', '')),
        );

        $appointments = $this->appointmentQuery()
            ->whereBetween('appointment_date', [
                $fromDate->toDateString(),
                $toDate->toDateString(),
            ])
            ->when($doctorId !== '', static fn (Builder $query) => $query->where('doctor_id', $doctorId))
            ->when($clinicId !== '', static fn (Builder $query) => $query->where('clinic_id', $clinicId))
            ->orderBy('appointment_date')
            ->orderBy('clinic_id')
            ->orderBy('doctor_id')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('appointments/queue', [
            'appointments' => $appointments,
            'filters' => [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'doctor_id' => $doctorId,
                'clinic_id' => $clinicId,
            ],
            'doctors' => Staff::query()
                ->doctors()
                ->when(
                    \App\Support\BranchContext::getActiveBranchId() !== null,
                    fn ($query) => $query->forActiveBranch()
                )
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(static fn (Staff $staff): array => [
                    'id' => $staff->id,
                    'name' => trim(sprintf('%s %s', $staff->first_name, $staff->last_name)),
                ])
                ->values()
                ->all(),
            'clinics' => Clinic::query()
                ->orderBy('clinic_name')
                ->get(['id', 'clinic_name'])
                ->map(static fn (Clinic $clinic): array => [
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                ])
                ->values()
                ->all(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('appointments/create', $this->formOptions());
    }

    public function store(StoreAppointmentRequest $request, CreateAppointment $action): RedirectResponse
    {
        $appointment = $action->handle($request->validated());

        return to_route('appointments.show', $appointment)->with('success', 'Appointment created successfully.');
    }

    public function show(Appointment $appointment): Response
    {
        $appointment->load([
            'patient:id,patient_number,first_name,last_name,middle_name,phone_number,email',
            'doctor:id,first_name,last_name',
            'clinic:id,clinic_name',
            'category:id,name',
            'mode:id,name,is_virtual',
            'branch:id,name',
            'visit:id,appointment_id,visit_number,status',
        ]);

        return Inertia::render('appointments/show', [
            'appointment' => $appointment,
            ...$this->formOptions($appointment),
            'statusOptions' => $this->statusOptions(),
            'visitTypes' => collect(VisitType::cases())
                ->map(static fn (VisitType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->all(),
            'billingTypes' => collect(PayerType::cases())
                ->map(static fn (PayerType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->all(),
            'insuranceCompanies' => InsuranceCompany::query()->orderBy('name')->get(['id', 'name']),
            'insurancePackages' => InsurancePackage::query()->orderBy('name')->get(['id', 'name', 'insurance_company_id']),
        ]);
    }

    public function update(
        UpdateAppointmentRequest $request,
        Appointment $appointment,
        UpdateAppointment $action,
    ): RedirectResponse {
        $action->handle($appointment, $request->validated());

        return to_route('appointments.show', $appointment)->with('success', 'Appointment updated successfully.');
    }

    public function confirm(
        ConfirmAppointmentRequest $request,
        Appointment $appointment,
        ConfirmAppointment $action,
    ): RedirectResponse {
        $action->handle($appointment);

        return to_route('appointments.show', $appointment)->with('success', 'Appointment confirmed successfully.');
    }

    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment,
        CancelAppointment $action,
    ): RedirectResponse {
        $action->handle($appointment, $request->validated());

        return to_route('appointments.show', $appointment)->with('success', 'Appointment cancelled successfully.');
    }

    public function markNoShow(
        MarkAppointmentNoShowRequest $request,
        Appointment $appointment,
        MarkAppointmentNoShow $action,
    ): RedirectResponse {
        $action->handle($appointment);

        return to_route('appointments.show', $appointment)->with('success', 'Appointment marked as no-show.');
    }

    public function reschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        RescheduleAppointment $action,
    ): RedirectResponse {
        $action->handle($appointment, $request->validated());

        return to_route('appointments.show', $appointment)->with('success', 'Appointment rescheduled successfully.');
    }

    public function checkIn(
        CheckInAppointmentRequest $request,
        Appointment $appointment,
        CheckInAppointment $action,
    ): RedirectResponse {
        $visit = $action->handle($appointment, $request->validated());

        return to_route('visits.show', $visit)->with('success', 'Appointment checked in successfully.');
    }

    private function formOptions(?Appointment $appointment = null): array
    {
        $patients = Patient::query()
            ->latest()
            ->limit(200)
            ->get([
                'id',
                'patient_number',
                'first_name',
                'last_name',
                'middle_name',
                'phone_number',
            ])
            ->map(static fn (Patient $patient): array => [
                'id' => $patient->id,
                'name' => trim(sprintf(
                    '%s %s %s',
                    $patient->first_name,
                    $patient->middle_name ?? '',
                    $patient->last_name
                )),
                'patient_number' => $patient->patient_number,
                'phone_number' => $patient->phone_number,
            ]);

        if (
            $appointment?->patient()->exists()
            && ! $patients->contains('id', $appointment->patient_id)
        ) {
            $patient = $appointment->patient()->first([
                'id',
                'patient_number',
                'first_name',
                'last_name',
                'middle_name',
                'phone_number',
            ]);

            if ($patient instanceof Patient) {
                $patients->prepend([
                    'id' => $patient->id,
                    'name' => trim(sprintf(
                        '%s %s %s',
                        $patient->first_name,
                        $patient->middle_name ?? '',
                        $patient->last_name
                    )),
                    'patient_number' => $patient->patient_number,
                    'phone_number' => $patient->phone_number,
                ]);
            }
        }

        $doctors = Staff::query()
            ->doctors()
            ->when(
                \App\Support\BranchContext::getActiveBranchId() !== null,
                fn ($query) => $query->forActiveBranch()
            )
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(static fn (Staff $staff): array => [
                'id' => $staff->id,
                'name' => trim(sprintf('%s %s', $staff->first_name, $staff->last_name)),
            ]);

        if (
            $appointment?->doctor()->exists()
            && ! $doctors->contains('id', $appointment->doctor_id)
        ) {
            $doctor = $appointment->doctor()->first(['id', 'first_name', 'last_name']);

            if ($doctor instanceof Staff) {
                $doctors->prepend([
                    'id' => $doctor->id,
                    'name' => trim(sprintf('%s %s', $doctor->first_name, $doctor->last_name)),
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

        if (
            $appointment?->clinic()->exists()
            && ! $clinics->contains('id', $appointment->clinic_id)
        ) {
            $clinic = $appointment->clinic()->first(['id', 'clinic_name']);

            if ($clinic instanceof Clinic) {
                $clinics->prepend([
                    'id' => $clinic->id,
                    'name' => $clinic->clinic_name,
                ]);
            }
        }

        $appointmentCategories = AppointmentCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (AppointmentCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        if (
            $appointment?->category()->exists()
            && ! $appointmentCategories->contains(
                'id',
                $appointment->appointment_category_id
            )
        ) {
            $category = $appointment->category()->first(['id', 'name']);

            if ($category instanceof AppointmentCategory) {
                $appointmentCategories->prepend([
                    'id' => $category->id,
                    'name' => $category->name,
                ]);
            }
        }

        $appointmentModes = AppointmentMode::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_virtual'])
            ->map(static fn (AppointmentMode $mode): array => [
                'id' => $mode->id,
                'name' => $mode->name,
                'is_virtual' => $mode->is_virtual,
            ]);

        if (
            $appointment?->mode()->exists()
            && ! $appointmentModes->contains('id', $appointment->appointment_mode_id)
        ) {
            $mode = $appointment->mode()->first(['id', 'name', 'is_virtual']);

            if ($mode instanceof AppointmentMode) {
                $appointmentModes->prepend([
                    'id' => $mode->id,
                    'name' => $mode->name,
                    'is_virtual' => $mode->is_virtual,
                ]);
            }
        }

        return [
            'patients' => $patients->values()->all(),
            'doctors' => $doctors->values()->all(),
            'clinics' => $clinics->values()->all(),
            'appointmentCategories' => $appointmentCategories->values()->all(),
            'appointmentModes' => $appointmentModes->values()->all(),
        ];
    }

    private function statusOptions(): array
    {
        return collect(AppointmentStatus::cases())
            ->map(static fn (AppointmentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    private function appointmentQuery(): Builder
    {
        return Appointment::query()->with([
            'patient:id,patient_number,first_name,last_name,middle_name,phone_number',
            'doctor:id,first_name,last_name',
            'clinic:id,clinic_name',
            'category:id,name',
            'mode:id,name,is_virtual',
            'visit:id,appointment_id,visit_number,status',
        ]);
    }
}
