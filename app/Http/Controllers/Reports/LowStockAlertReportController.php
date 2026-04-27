<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\GenerateLowStockAlertReportAction;
use App\Models\User;
use App\Support\BranchContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class LowStockAlertReportController implements HasMiddleware
{
    public function __construct(private GenerateLowStockAlertReportAction $report) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.view', only: ['index', 'download']),
        ];
    }

    public function index(Request $request): Response
    {
        /** @var array{location_id?: string|null} $filters */
        $filters = $request->validate([
            'location_id' => ['nullable', 'string'],
        ]);

        $branchId = BranchContext::getActiveBranchId() ?? '';
        $user = $request->user();

        $report = $branchId !== '' && $user instanceof User
            ? $this->report->handle($branchId, $user, $this->locationId($filters))
            : null;

        return Inertia::render('reports/low-stock', [
            'report' => $report,
            'filters' => [
                'location_id' => $this->locationId($filters),
            ],
        ]);
    }

    public function download(Request $request): SymfonyResponse
    {
        /** @var array{location_id?: string|null} $filters */
        $filters = $request->validate([
            'location_id' => ['nullable', 'string'],
        ]);

        $branchId = BranchContext::getActiveBranchId() ?? '';
        abort_if($branchId === '', 422, 'No active branch selected.');

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        /**
         * @var array{branch_name: string|null, total_alerts: int, critical_count: int, low_count: int, out_of_stock_count: int, selected_location_id: string|null, locations: Collection<int, array{id: string, name: string, code: string|null}>, rows: Collection<int, array{item_id: string, item_name: string, dosage_info: string, unit: string|null, location_id: string, location_name: string, location_code: string, minimum_stock_level: float, reorder_level: float, quantity: float, status: string}>} $data
         */
        $data = $this->report->handle($branchId, $user, $this->locationId($filters));

        $selectedLocation = $data['locations']
            ->first(fn (array $location): bool => $location['id'] === $data['selected_location_id']);
        $selectedLocationName = is_array($selectedLocation)
            ? $selectedLocation['name']
            : 'All Locations';

        $pdf = Pdf::loadView('reports.low-stock', [
            'facilityName' => $data['branch_name'] ?? config('app.name'),
            'reportTitle' => 'Low Stock Alert Report',
            'reportPeriod' => now()->format('d M Y, H:i'),
            'generatedBy' => $user->name,
            'appliedFilters' => [
                'As at' => now()->format('d M Y H:i'),
                'Location' => $selectedLocationName,
            ],
            'rows' => $data['rows'],
            'locations' => $data['locations'],
            'total_alerts' => $data['total_alerts'],
            'critical_count' => $data['critical_count'],
            'low_count' => $data['low_count'],
            'out_of_stock_count' => $data['out_of_stock_count'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('low-stock-alert-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @param  array{location_id?: string|null}  $filters
     */
    private function locationId(array $filters): ?string
    {
        $locationId = $filters['location_id'] ?? null;

        return is_string($locationId) && $locationId !== '' ? $locationId : null;
    }
}
