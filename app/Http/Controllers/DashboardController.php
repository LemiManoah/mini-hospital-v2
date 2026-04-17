<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Support\ActiveBranchWorkspace;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:dashboard.view', only: ['index']),
        ];
    }

    public function index(): Response
    {
        $today = now()->toDateString();

        $visitQuery = $this->activeBranchWorkspace->apply(PatientVisit::query());
        $appointmentQuery = $this->activeBranchWorkspace->apply(Appointment::query());
        $labRequestQuery = $this->activeBranchWorkspace->apply(LabRequest::query());
        $patientQuery = Patient::query();

        $metrics = [
            [
                'label' => 'Patients Today',
                'value' => (clone $visitQuery)->whereDate('created_at', $today)->count(),
                'hint' => 'Visits registered today',
                'icon' => 'users',
                'color' => 'blue',
            ],
            [
                'label' => 'Active Visits',
                'value' => (clone $visitQuery)
                    ->whereIn('status', [
                        VisitStatus::REGISTERED->value,
                        VisitStatus::IN_PROGRESS->value,
                        VisitStatus::AWAITING_PAYMENT->value,
                    ])
                    ->count(),
                'hint' => 'Currently in progress',
                'icon' => 'activity',
                'color' => 'green',
            ],
            [
                'label' => 'Appointments Today',
                'value' => (clone $appointmentQuery)->whereDate('appointment_date', $today)->count(),
                'hint' => 'Scheduled for today',
                'icon' => 'calendar',
                'color' => 'purple',
            ],
            [
                'label' => 'Pending Lab Results',
                'value' => (clone $labRequestQuery)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'hint' => 'Lab requests awaiting completion',
                'icon' => 'flask',
                'color' => 'orange',
            ],
            [
                'label' => 'Total Patients',
                'value' => (clone $patientQuery)->count(),
                'hint' => 'All registered patients',
                'icon' => 'user-check',
                'color' => 'teal',
            ],
            [
                'label' => 'Completed Visits',
                'value' => (clone $visitQuery)
                    ->where('status', VisitStatus::COMPLETED->value)
                    ->whereDate('updated_at', $today)
                    ->count(),
                'hint' => 'Visits completed today',
                'icon' => 'check-circle',
                'color' => 'emerald',
            ],
        ];

        $visitStatusCounts = [
            [
                'label' => 'Registered',
                'value' => 'registered',
                'count' => (clone $visitQuery)->where('status', VisitStatus::REGISTERED->value)->count(),
            ],
            [
                'label' => 'In Progress',
                'value' => 'in_progress',
                'count' => (clone $visitQuery)->where('status', VisitStatus::IN_PROGRESS->value)->count(),
            ],
            [
                'label' => 'Awaiting Payment',
                'value' => 'awaiting_payment',
                'count' => (clone $visitQuery)->where('status', VisitStatus::AWAITING_PAYMENT->value)->count(),
            ],
            [
                'label' => 'Completed',
                'value' => 'completed',
                'count' => (clone $visitQuery)->where('status', VisitStatus::COMPLETED->value)->count(),
            ],
        ];

        $appointmentStatusCounts = [
            [
                'label' => 'Scheduled',
                'value' => 'scheduled',
                'count' => (clone $appointmentQuery)->where('status', AppointmentStatus::SCHEDULED->value)->count(),
            ],
            [
                'label' => 'Confirmed',
                'value' => 'confirmed',
                'count' => (clone $appointmentQuery)->where('status', AppointmentStatus::CONFIRMED->value)->count(),
            ],
            [
                'label' => 'Checked In',
                'value' => 'checked_in',
                'count' => (clone $appointmentQuery)->where('status', AppointmentStatus::CHECKED_IN->value)->count(),
            ],
            [
                'label' => 'Completed',
                'value' => 'completed',
                'count' => (clone $appointmentQuery)->where('status', AppointmentStatus::COMPLETED->value)->count(),
            ],
            [
                'label' => 'No Show',
                'value' => 'no_show',
                'count' => (clone $appointmentQuery)->where('status', AppointmentStatus::NO_SHOW->value)->count(),
            ],
        ];

        $recentVisits = (clone $visitQuery)
            ->with([
                'patient:id,patient_number,first_name,last_name',
                'doctor:id,first_name,last_name',
            ])
            ->latest()
            ->limit(8)
            ->get();

        $recentAppointments = (clone $appointmentQuery)
            ->with([
                'patient:id,patient_number,first_name,last_name',
                'doctor:id,first_name,last_name',
                'clinic:id,clinic_name',
            ])
            ->whereDate('appointment_date', $today)
            ->orderBy('start_time')
            ->limit(6)
            ->get();

        return Inertia::render('dashboard', [
            'metrics' => $metrics,
            'visit_status_counts' => $visitStatusCounts,
            'appointment_status_counts' => $appointmentStatusCounts,
            'recent_visits' => $recentVisits,
            'recent_appointments' => $recentAppointments,
        ]);
    }
}
