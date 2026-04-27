<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\GenerateDailyRevenueReportAction;
use App\Models\Payment;
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

final readonly class DailyRevenueReportController implements HasMiddleware
{
    public function __construct(private GenerateDailyRevenueReportAction $report) {}

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
            ? $this->report->handle($date, $branchId)
            : null;

        return Inertia::render('reports/daily-revenue', [
            'report' => $report,
            'filters' => ['date' => $date->toDateString()],
        ]);
    }

    public function download(Request $request): SymfonyResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $dateInput = $request->input('date');

        $date = is_string($dateInput) && $dateInput !== ''
            ? Date::parse($dateInput)
            : now();
        $branchId = BranchContext::getActiveBranchId() ?? '';

        abort_if($branchId === '', 422, 'No active branch selected.');
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        /**
         * @var array{date: string, branch_name: string|null, currency: string, total_amount: float, total_count: int, refund_amount: float, net_amount: float, by_method: array<string, float>, rows: Collection<int, Payment>} $data
         */
        $data = $this->report->handle($date, $branchId);

        $pdf = Pdf::loadView('reports.daily-revenue', array_merge($data, [
            'facilityName' => $data['branch_name'] ?? config('app.name'),
            'reportTitle' => 'Daily Revenue Report',
            'reportPeriod' => $date->format('d M Y'),
            'generatedBy' => $user->name,
            'appliedFilters' => ['Date' => $date->format('d M Y')],
        ]))->setPaper('a4');

        return $pdf->download(sprintf('daily-revenue-%s.pdf', $date->format('Y-m-d')));
    }
}
