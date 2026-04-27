<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\BuildReportGeneratorAction;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ReportGeneratorController implements HasMiddleware
{
    public function __construct(private BuildReportGeneratorAction $reportGenerator) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.view', only: ['index', 'exportCsv']),
        ];
    }

    public function index(Request $request): Response
    {
        /** @var array{report?: string|null, date?: string|null, location_id?: string|null, doctor_id?: string|null} $filters */
        $filters = $request->validate([
            'report' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'location_id' => ['nullable', 'string'],
            'doctor_id' => ['nullable', 'string'],
        ]);

        $branchId = BranchContext::getActiveBranchId() ?? '';
        $user = $request->user();

        abort_if($branchId === '', 422, 'No active branch selected.');
        abort_unless($user instanceof User, 401);

        $payload = $this->reportGenerator->handle($branchId, $user, $filters);
        $query = $this->queryForSelectedReport($payload['selected_report'], $payload['filters']);
        $preview = $payload['preview'];
        $preview['pdf_url'] = route($preview['pdf_route_name'], $query);
        $preview['csv_url'] = route('reports.export-csv', array_merge(['report' => $payload['selected_report']], $query));
        $preview['legacy_url'] = route($preview['legacy_route_name'], $query);

        return Inertia::render('reports/index', [
            'reports' => $payload['reports'],
            'selectedReport' => $payload['selected_report'],
            'filters' => $payload['filters'],
            'preview' => $preview,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var array{report?: string|null, date?: string|null, location_id?: string|null, doctor_id?: string|null} $filters */
        $filters = $request->validate([
            'report' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'location_id' => ['nullable', 'string'],
            'doctor_id' => ['nullable', 'string'],
        ]);

        $branchId = BranchContext::getActiveBranchId() ?? '';
        $user = $request->user();

        abort_if($branchId === '', 422, 'No active branch selected.');
        abort_unless($user instanceof User, 401);

        $payload = $this->reportGenerator->handle($branchId, $user, $filters);
        $preview = $payload['preview'];
        $filename = sprintf('%s-%s.csv', $preview['filename_prefix'], now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($preview): void {
            $handle = fopen('php://output', 'w');
            throw_if($handle === false, RuntimeException::class, 'Unable to open output stream for report export.');

            fputcsv(
                $handle,
                array_map(static fn (array $column): string => $column['label'], $preview['columns']),
                escape: '\\',
            );

            foreach ($preview['rows'] as $row) {
                fputcsv(
                    $handle,
                    array_map(
                        static fn (array $column): string => (string) ($row[$column['key']] ?? ''),
                        $preview['columns'],
                    ),
                    escape: '\\',
                );
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  array{date: string|null, location_id: string|null, doctor_id: string|null}  $filters
     * @return array<string, string>
     */
    private function queryForSelectedReport(string $selectedReport, array $filters): array
    {
        return match ($selectedReport) {
            'stock-level', 'low-stock' => array_filter([
                'location_id' => $filters['location_id'],
            ]),
            'appointment-schedule' => array_filter([
                'date' => $filters['date'],
                'doctor_id' => $filters['doctor_id'],
            ]),
            default => array_filter([
                'date' => $filters['date'],
            ]),
        };
    }
}
