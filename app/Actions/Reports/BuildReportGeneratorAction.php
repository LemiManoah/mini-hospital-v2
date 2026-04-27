<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\InventoryLocation;
use App\Models\Staff;
use App\Models\User;
use App\Support\InventoryLocationAccess;
use Illuminate\Support\Facades\Date;

final readonly class BuildReportGeneratorAction
{
    public function __construct(
        private GenerateDailyRevenueReportAction $dailyRevenueReport,
        private GenerateStockLevelReportAction $stockLevelReport,
        private GenerateLowStockAlertReportAction $lowStockReport,
        private GenerateAppointmentScheduleReportAction $appointmentScheduleReport,
        private InventoryLocationAccess $locationAccess,
    ) {}

    /**
     * @param  array{report?: string|null, date?: string|null, location_id?: string|null, doctor_id?: string|null}  $filters
     * @return array{
     *     reports: list<array{
     *         key: string,
     *         label: string,
     *         description: string,
     *         filters: list<array{
     *             name: string,
     *             label: string,
     *             type: string,
     *             placeholder?: string,
     *             options?: list<array{value: string, label: string}>
     *         }>
     *     }>,
     *     selected_report: string,
     *     filters: array{date: string|null, location_id: string|null, doctor_id: string|null},
     *     preview: array{
     *         title: string,
     *         description: string,
     *         summary: list<array{label: string, value: string, tone: string}>,
     *         columns: list<array{key: string, label: string, align: string}>,
     *         rows: list<array<string, string>>,
     *         empty_message: string,
     *         legacy_route_name: string,
     *         pdf_route_name: string,
     *         filename_prefix: string
     *     }
     * }
     */
    public function handle(string $branchId, ?User $user, array $filters): array
    {
        $locationOptions = $this->locationOptions($branchId, $user);
        $doctorOptions = $this->doctorOptions();
        $reports = $this->definitions($locationOptions, $doctorOptions);
        $selectedReport = $this->selectedReport($filters['report'] ?? null, array_keys($reports));
        $normalizedFilters = $this->normalizedFilters($selectedReport, $filters);

        return [
            'reports' => array_values($reports),
            'selected_report' => $selectedReport,
            'filters' => $normalizedFilters,
            'preview' => $this->preview($selectedReport, $branchId, $user, $normalizedFilters),
        ];
    }

    /**
     * @param  list<array{value: string, label: string}>  $locationOptions
     * @param  list<array{value: string, label: string}>  $doctorOptions
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     filters: list<array{
     *         name: string,
     *         label: string,
     *         type: string,
     *         placeholder?: string,
     *         options?: list<array{value: string, label: string}>
     *     }>
     * }>
     */
    private function definitions(array $locationOptions, array $doctorOptions): array
    {
        return [
            'daily-revenue' => [
                'key' => 'daily-revenue',
                'label' => 'Daily Revenue',
                'description' => 'Payments collected on a given day, including refunds and payment-method totals.',
                'filters' => [
                    [
                        'name' => 'date',
                        'label' => 'Date',
                        'type' => 'date',
                    ],
                ],
            ],
            'stock-level' => [
                'key' => 'stock-level',
                'label' => 'Stock Level',
                'description' => 'Current stock quantities by location with low-stock and out-of-stock indicators.',
                'filters' => [
                    [
                        'name' => 'location_id',
                        'label' => 'Location',
                        'type' => 'select',
                        'placeholder' => 'All locations',
                        'options' => $locationOptions,
                    ],
                ],
            ],
            'low-stock' => [
                'key' => 'low-stock',
                'label' => 'Low Stock Alerts',
                'description' => 'Items below reorder or minimum thresholds, grouped into low, critical, and out-of-stock.',
                'filters' => [
                    [
                        'name' => 'location_id',
                        'label' => 'Location',
                        'type' => 'select',
                        'placeholder' => 'All locations',
                        'options' => $locationOptions,
                    ],
                ],
            ],
            'appointment-schedule' => [
                'key' => 'appointment-schedule',
                'label' => 'Appointment Schedule',
                'description' => 'Appointments for a selected day, with optional filtering by doctor.',
                'filters' => [
                    [
                        'name' => 'date',
                        'label' => 'Date',
                        'type' => 'date',
                    ],
                    [
                        'name' => 'doctor_id',
                        'label' => 'Doctor',
                        'type' => 'select',
                        'placeholder' => 'All doctors',
                        'options' => $doctorOptions,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array{date: string|null, location_id: string|null, doctor_id: string|null}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: list<array{label: string, value: string, tone: string}>,
     *     columns: list<array{key: string, label: string, align: string}>,
     *     rows: list<array<string, string>>,
     *     empty_message: string,
     *     legacy_route_name: string,
     *     pdf_route_name: string,
     *     filename_prefix: string
     * }
     */
    private function preview(string $selectedReport, string $branchId, ?User $user, array $filters): array
    {
        return match ($selectedReport) {
            'stock-level' => $this->stockLevelPreview($branchId, $user, $filters['location_id']),
            'low-stock' => $this->lowStockPreview($branchId, $user, $filters['location_id']),
            'appointment-schedule' => $this->appointmentSchedulePreview($branchId, $filters['date'], $filters['doctor_id']),
            default => $this->dailyRevenuePreview($branchId, $filters['date']),
        };
    }

    /**
     * @param  array{report?: string|null, date?: string|null, location_id?: string|null, doctor_id?: string|null}  $filters
     * @return array{date: string|null, location_id: string|null, doctor_id: string|null}
     */
    private function normalizedFilters(string $selectedReport, array $filters): array
    {
        $date = $this->dateFilter($filters['date'] ?? null);

        return match ($selectedReport) {
            'stock-level', 'low-stock' => [
                'date' => null,
                'location_id' => $this->stringFilter($filters['location_id'] ?? null),
                'doctor_id' => null,
            ],
            'appointment-schedule' => [
                'date' => $date,
                'location_id' => null,
                'doctor_id' => $this->stringFilter($filters['doctor_id'] ?? null),
            ],
            default => [
                'date' => $date,
                'location_id' => null,
                'doctor_id' => null,
            ],
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function locationOptions(string $branchId, ?User $user): array
    {
        /** @var list<array{value: string, label: string}> $options */
        $options = $this->locationAccess
            ->accessibleLocations($user, $branchId)
            ->map(static fn (InventoryLocation $location): array => [
                'value' => $location->id,
                'label' => sprintf('%s (%s)', $location->name, $location->location_code),
            ])
            ->values()
            ->all();

        return $options;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function doctorOptions(): array
    {
        /** @var list<array{value: string, label: string}> $options */
        $options = Staff::query()
            ->doctors()
            ->forActiveBranch()
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(static fn (Staff $staff): array => [
                'value' => $staff->id,
                'label' => mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name)),
            ])
            ->values()
            ->all();

        return $options;
    }

    /**
     * @param  list<string>  $availableReports
     */
    private function selectedReport(?string $report, array $availableReports): string
    {
        return is_string($report) && in_array($report, $availableReports, true)
            ? $report
            : 'daily-revenue';
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: list<array{label: string, value: string, tone: string}>,
     *     columns: list<array{key: string, label: string, align: string}>,
     *     rows: list<array<string, string>>,
     *     empty_message: string,
     *     legacy_route_name: string,
     *     pdf_route_name: string,
     *     filename_prefix: string
     * }
     */
    private function dailyRevenuePreview(string $branchId, ?string $date): array
    {
        $reportDate = Date::parse($date ?? now()->toDateString());
        $data = $this->dailyRevenueReport->handle($reportDate, $branchId);
        /** @var list<array<string, string>> $rows */
        $rows = array_values($data['rows']->map(fn ($payment): array => [
            'receipt_number' => $payment->receipt_number ?? '-',
            'patient' => $payment->visit?->patient !== null
                ? mb_trim(sprintf('%s %s %s', $payment->visit->patient->first_name, $payment->visit->patient->middle_name ?? '', $payment->visit->patient->last_name))
                : '-',
            'visit_number' => $payment->visit->visit_number ?? '-',
            'method' => $payment->payment_method !== null ? str_replace('_', ' ', $payment->payment_method) : '-',
            'reference' => $payment->reference_number ?? '-',
            'amount' => $this->money($data['currency'], (float) $payment->amount),
            'time' => $payment->payment_date?->format('H:i') ?? '-',
            'type' => $payment->is_refund ? 'Refund' : 'Payment',
        ])->values()->all());

        return [
            'title' => 'Daily Revenue Report',
            'description' => 'Preview of payments collected, refunds recorded, and totals by payment method.',
            'summary' => [
                ['label' => 'Total Collected', 'value' => $this->money($data['currency'], $data['total_amount']), 'tone' => 'primary'],
                ['label' => 'Refunds', 'value' => $this->money($data['currency'], $data['refund_amount']), 'tone' => 'danger'],
                ['label' => 'Net Revenue', 'value' => $this->money($data['currency'], $data['net_amount']), 'tone' => 'success'],
                ['label' => 'Transactions', 'value' => (string) $data['total_count'], 'tone' => 'muted'],
            ],
            'columns' => [
                ['key' => 'receipt_number', 'label' => 'Receipt No.', 'align' => 'left'],
                ['key' => 'patient', 'label' => 'Patient', 'align' => 'left'],
                ['key' => 'visit_number', 'label' => 'Visit No.', 'align' => 'left'],
                ['key' => 'method', 'label' => 'Method', 'align' => 'left'],
                ['key' => 'reference', 'label' => 'Reference', 'align' => 'left'],
                ['key' => 'amount', 'label' => 'Amount', 'align' => 'right'],
                ['key' => 'time', 'label' => 'Time', 'align' => 'left'],
                ['key' => 'type', 'label' => 'Type', 'align' => 'left'],
            ],
            'rows' => $rows,
            'empty_message' => 'No payments were recorded for the selected date.',
            'legacy_route_name' => 'reports.daily-revenue.index',
            'pdf_route_name' => 'reports.daily-revenue.download',
            'filename_prefix' => 'daily-revenue',
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: list<array{label: string, value: string, tone: string}>,
     *     columns: list<array{key: string, label: string, align: string}>,
     *     rows: list<array<string, string>>,
     *     empty_message: string,
     *     legacy_route_name: string,
     *     pdf_route_name: string,
     *     filename_prefix: string
     * }
     */
    private function stockLevelPreview(string $branchId, ?User $user, ?string $locationId): array
    {
        $data = $this->stockLevelReport->handle($branchId, $user, $locationId);
        /** @var list<array<string, string>> $rows */
        $rows = array_values($data['rows']->map(static fn (array $row): array => [
            'item_name' => $row['item_name'],
            'dosage_info' => $row['dosage_info'] !== '' ? $row['dosage_info'] : '-',
            'location' => sprintf('%s (%s)', $row['location_name'], $row['location_code']),
            'minimum_stock_level' => number_format($row['minimum_stock_level'], 0),
            'reorder_level' => number_format($row['reorder_level'], 0),
            'quantity' => number_format($row['quantity'], 2),
            'unit' => $row['unit'] ?? '-',
            'status' => str_replace('_', ' ', $row['status']),
        ])->values()->all());

        return [
            'title' => 'Stock Level Report',
            'description' => 'Preview of item quantities at each accessible location, including adequacy status.',
            'summary' => [
                ['label' => 'Total Items', 'value' => (string) $data['total_items'], 'tone' => 'primary'],
                ['label' => 'Low / Critical', 'value' => (string) $data['low_stock_count'], 'tone' => 'warning'],
                ['label' => 'Out of Stock', 'value' => (string) $data['out_of_stock_count'], 'tone' => 'danger'],
                ['label' => 'Adequate', 'value' => (string) ($data['total_items'] - $data['low_stock_count'] - $data['out_of_stock_count']), 'tone' => 'success'],
            ],
            'columns' => [
                ['key' => 'item_name', 'label' => 'Item Name', 'align' => 'left'],
                ['key' => 'dosage_info', 'label' => 'Dosage / Form', 'align' => 'left'],
                ['key' => 'location', 'label' => 'Location', 'align' => 'left'],
                ['key' => 'minimum_stock_level', 'label' => 'Min. Level', 'align' => 'right'],
                ['key' => 'reorder_level', 'label' => 'Reorder', 'align' => 'right'],
                ['key' => 'quantity', 'label' => 'Current Qty', 'align' => 'right'],
                ['key' => 'unit', 'label' => 'Unit', 'align' => 'left'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ],
            'rows' => $rows,
            'empty_message' => 'No stock data was found for the selected location.',
            'legacy_route_name' => 'reports.stock-level.index',
            'pdf_route_name' => 'reports.stock-level.download',
            'filename_prefix' => 'stock-level',
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: list<array{label: string, value: string, tone: string}>,
     *     columns: list<array{key: string, label: string, align: string}>,
     *     rows: list<array<string, string>>,
     *     empty_message: string,
     *     legacy_route_name: string,
     *     pdf_route_name: string,
     *     filename_prefix: string
     * }
     */
    private function lowStockPreview(string $branchId, ?User $user, ?string $locationId): array
    {
        $data = $this->lowStockReport->handle($branchId, $user, $locationId);
        /** @var list<array<string, string>> $rows */
        $rows = array_values($data['rows']->map(static fn (array $row): array => [
            'item_name' => $row['item_name'],
            'dosage_info' => $row['dosage_info'] !== '' ? $row['dosage_info'] : '-',
            'location' => sprintf('%s (%s)', $row['location_name'], $row['location_code']),
            'minimum_stock_level' => number_format($row['minimum_stock_level'], 0),
            'reorder_level' => number_format($row['reorder_level'], 0),
            'quantity' => number_format($row['quantity'], 2),
            'unit' => $row['unit'] ?? '-',
            'status' => str_replace('_', ' ', $row['status']),
        ])->values()->all());

        return [
            'title' => 'Low Stock Alert Report',
            'description' => 'Preview of stock alerts for items that need replenishment attention.',
            'summary' => [
                ['label' => 'Total Alerts', 'value' => (string) $data['total_alerts'], 'tone' => 'primary'],
                ['label' => 'Critical', 'value' => (string) $data['critical_count'], 'tone' => 'warning'],
                ['label' => 'Low', 'value' => (string) $data['low_count'], 'tone' => 'muted'],
                ['label' => 'Out of Stock', 'value' => (string) $data['out_of_stock_count'], 'tone' => 'danger'],
            ],
            'columns' => [
                ['key' => 'item_name', 'label' => 'Item Name', 'align' => 'left'],
                ['key' => 'dosage_info', 'label' => 'Dosage / Form', 'align' => 'left'],
                ['key' => 'location', 'label' => 'Location', 'align' => 'left'],
                ['key' => 'minimum_stock_level', 'label' => 'Min. Level', 'align' => 'right'],
                ['key' => 'reorder_level', 'label' => 'Reorder', 'align' => 'right'],
                ['key' => 'quantity', 'label' => 'Current Qty', 'align' => 'right'],
                ['key' => 'unit', 'label' => 'Unit', 'align' => 'left'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ],
            'rows' => $rows,
            'empty_message' => 'No low stock alerts were found for the selected location.',
            'legacy_route_name' => 'reports.low-stock.index',
            'pdf_route_name' => 'reports.low-stock.download',
            'filename_prefix' => 'low-stock-alert',
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: list<array{label: string, value: string, tone: string}>,
     *     columns: list<array{key: string, label: string, align: string}>,
     *     rows: list<array<string, string>>,
     *     empty_message: string,
     *     legacy_route_name: string,
     *     pdf_route_name: string,
     *     filename_prefix: string
     * }
     */
    private function appointmentSchedulePreview(string $branchId, ?string $date, ?string $doctorId): array
    {
        $reportDate = Date::parse($date ?? now()->toDateString());
        $data = $this->appointmentScheduleReport->handle($reportDate, $branchId, $doctorId);
        /** @var list<array<string, string>> $rows */
        $rows = array_values($data['rows']->map(static fn ($appointment): array => [
            'time' => sprintf('%s - %s', mb_substr((string) $appointment->start_time, 0, 5), mb_substr((string) $appointment->end_time, 0, 5)),
            'patient' => $appointment->patient !== null
                ? mb_trim(sprintf('%s %s %s', $appointment->patient->first_name, $appointment->patient->middle_name ?? '', $appointment->patient->last_name))
                : '-',
            'doctor' => $appointment->doctor !== null
                ? mb_trim(sprintf('%s %s', $appointment->doctor->first_name, $appointment->doctor->last_name))
                : '-',
            'clinic' => $appointment->clinic->clinic_name ?? '-',
            'mode' => $appointment->mode->name ?? '-',
            'status' => $appointment->status->label(),
            'reason' => $appointment->reason_for_visit ?? '-',
        ])->values()->all());

        return [
            'title' => 'Appointment Schedule Report',
            'description' => 'Preview of daily appointments with timing, doctor assignment, and status.',
            'summary' => [
                ['label' => 'Total Appointments', 'value' => (string) $data['total'], 'tone' => 'primary'],
                ['label' => 'Scheduled', 'value' => (string) ($data['by_status']['scheduled'] ?? 0), 'tone' => 'muted'],
                ['label' => 'Completed', 'value' => (string) ($data['by_status']['completed'] ?? 0), 'tone' => 'success'],
                ['label' => 'Cancelled / No Show', 'value' => (string) (($data['by_status']['cancelled'] ?? 0) + ($data['by_status']['no_show'] ?? 0)), 'tone' => 'danger'],
            ],
            'columns' => [
                ['key' => 'time', 'label' => 'Time', 'align' => 'left'],
                ['key' => 'patient', 'label' => 'Patient', 'align' => 'left'],
                ['key' => 'doctor', 'label' => 'Doctor', 'align' => 'left'],
                ['key' => 'clinic', 'label' => 'Clinic', 'align' => 'left'],
                ['key' => 'mode', 'label' => 'Mode', 'align' => 'left'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
                ['key' => 'reason', 'label' => 'Reason', 'align' => 'left'],
            ],
            'rows' => $rows,
            'empty_message' => 'No appointments were found for the selected date and doctor.',
            'legacy_route_name' => 'reports.appointment-schedule.index',
            'pdf_route_name' => 'reports.appointment-schedule.download',
            'filename_prefix' => 'appointment-schedule',
        ];
    }

    private function money(string $currency, float $amount): string
    {
        return sprintf(
            '%s %s',
            $currency,
            number_format($amount, 2, '.', ','),
        );
    }

    private function dateFilter(?string $date): string
    {
        return is_string($date) && $date !== ''
            ? Date::parse($date)->toDateString()
            : now()->toDateString();
    }

    private function stringFilter(?string $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
