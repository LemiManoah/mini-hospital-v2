<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\User;
use Illuminate\Support\Collection;

final readonly class GenerateLowStockAlertReportAction
{
    public function __construct(private GenerateStockLevelReportAction $stockLevelReport) {}

    /**
     * @return array{
     *     branch_name: string|null,
     *     total_alerts: int,
     *     critical_count: int,
     *     low_count: int,
     *     out_of_stock_count: int,
     *     selected_location_id: string|null,
     *     locations: Collection<int, array{id: string, name: string, code: string|null}>,
     *     rows: Collection<int, array{
     *         item_id: string,
     *         item_name: string,
     *         dosage_info: string,
     *         unit: string|null,
     *         location_id: string,
     *         location_name: string,
     *         location_code: string,
     *         minimum_stock_level: float,
     *         reorder_level: float,
     *         quantity: float,
     *         status: string
     *     }>
     * }
     */
    public function handle(string $branchId, ?User $user = null, ?string $locationId = null): array
    {
        /** @var array{
         *     branch_name: string|null,
         *     total_items: int,
         *     low_stock_count: int,
         *     out_of_stock_count: int,
         *     selected_location_id: string|null,
         *     locations: Collection<int, array{id: string, name: string, code: string|null}>,
         *     rows: Collection<int, array{
         *         item_id: string,
         *         item_name: string,
         *         dosage_info: string,
         *         unit: string|null,
         *         location_id: string,
         *         location_name: string,
         *         location_code: string,
         *         minimum_stock_level: float,
         *         reorder_level: float,
         *         quantity: float,
         *         status: string
         *     }>
         * } $stockReport
         */
        $stockReport = $this->stockLevelReport->handle($branchId, $user, $locationId);

        $availableLocationIds = $stockReport['locations']->pluck('id')->all();
        $selectedLocationId = is_string($locationId) && in_array($locationId, $availableLocationIds, true)
            ? $locationId
            : null;

        $rows = $stockReport['rows']
            ->filter(function (array $row) use ($selectedLocationId): bool {
                if (! in_array($row['status'], ['low', 'critical', 'out_of_stock'], true)) {
                    return false;
                }

                if ($selectedLocationId === null) {
                    return true;
                }

                return $row['location_id'] === $selectedLocationId;
            })
            ->values();

        return [
            'branch_name' => $stockReport['branch_name'],
            'total_alerts' => $rows->count(),
            'critical_count' => $rows->where('status', 'critical')->count(),
            'low_count' => $rows->where('status', 'low')->count(),
            'out_of_stock_count' => $rows->where('status', 'out_of_stock')->count(),
            'selected_location_id' => $selectedLocationId,
            'locations' => $stockReport['locations'],
            'rows' => $rows,
        ];
    }
}
