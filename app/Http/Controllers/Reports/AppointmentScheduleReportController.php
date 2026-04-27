<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\GenerateAppointmentScheduleReportAction;
use App\Models\Appointment;
use App\Models\User;
use App\Support\BranchContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class AppointmentScheduleReportController implements HasMiddleware
{
    public function __construct(private GenerateAppointmentScheduleReportAction $report) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.view', only: ['index', 'download']),
        ];
    }

    public function index(Request $request): Response
    {
        $date = $request->date('date') ?? now();
        $branchId = BranchContext::getActiveBranchId() ?? '';

        $report = $branchId !== ''
            ? $this->report->handle($date, $branchId, $request->query('doctor_id'))
            : null;

        return Inertia::render('reports/appointment-schedule', [
            'report' => $report,
            'filters' => [
                'date' => $date->toDateString(),
                'doctor_id' => $request->query('doctor_id'),
            ],
        ]);
    }

    public function download(Request $request): SymfonyResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'doctor_id' => ['nullable', 'string'],
        ]);

        $dateInput = $request->input('date');
        $doctorIdInput = $request->input('doctor_id');

        $date = is_string($dateInput) && $dateInput !== ''
            ? Date::parse($dateInput)
            : now();
        $branchId = BranchContext::getActiveBranchId() ?? '';

        abort_if($branchId === '', 422, 'No active branch selected.');
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        /**
         * @var array{date: string, day_of_week: string, branch_name: string|null, total: int, by_status: array<string, int>, rows: Collection<int, Appointment>} $data
         */
        $data = $this->report->handle(
            $date,
            $branchId,
            is_string($doctorIdInput) && $doctorIdInput !== '' ? $doctorIdInput : null,
        );

        $pdf = Pdf::loadView('reports.appointment-schedule', [
            'facilityName' => $data['branch_name'] ?? config('app.name'),
            'reportTitle' => 'Appointment Schedule',
            'reportPeriod' => $date->format('l, d M Y'),
            'generatedBy' => $user->name,
            'appliedFilters' => ['Date' => $date->format('d M Y')],
            'rows' => $data['rows'],
            'total' => $data['total'],
            'by_status' => $data['by_status'],
            'day_of_week' => $data['day_of_week'],
        ])->setPaper('a4');

        return $pdf->download(sprintf('appointment-schedule-%s.pdf', $date->format('Y-m-d')));
    }
}
