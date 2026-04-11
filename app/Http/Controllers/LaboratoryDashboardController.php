<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LabRequestStatus;
use App\Enums\InventoryLocationType;
use App\Models\InventoryBatch;
use App\Models\InventoryLocationItem;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\StockMovement;
use App\Support\ActiveBranchWorkspace;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LaboratoryDashboardController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_requests.view', only: ['index']),
        ];
    }

    public function index(): Response
    {
        $today = now()->toDateString();
        $activeBranchId = BranchContext::getActiveBranchId();

        $requestQuery = $this->activeBranchWorkspace->apply(LabRequest::query());
        $labLocationIds = $this->inventoryLocationAccess
            ->accessibleLocationIds(Auth::user(), $activeBranchId, [InventoryLocationType::LABORATORY]);

        $metrics = [
            [
                'label' => 'Requests Today',
                'value' => (clone $requestQuery)->whereDate('request_date', $today)->count(),
                'hint' => 'New requests created in the active branch today.',
            ],
            [
                'label' => 'Urgent Open Requests',
                'value' => (clone $requestQuery)
                    ->whereIn('priority', ['urgent', 'stat', 'critical'])
                    ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
                    ->count(),
                'hint' => 'Requests that still need fast laboratory attention.',
            ],
            [
                'label' => 'Pending Review',
                'value' => $this->itemQuery()
                    ->whereNotNull('result_entered_at')
                    ->whereNull('reviewed_at')
                    ->whereNull('approved_at')
                    ->count(),
                'hint' => 'Results entered but not yet reviewed.',
            ],
            [
                'label' => 'Released Today',
                'value' => $this->itemQuery()
                    ->whereNotNull('approved_at')
                    ->whereDate('approved_at', $today)
                    ->count(),
                'hint' => 'Tests approved and released to clinicians today.',
            ],
        ];

        $labLocationItems = is_string($activeBranchId) && $activeBranchId !== '' && $labLocationIds !== []
            ? InventoryLocationItem::query()
                ->where('branch_id', $activeBranchId)
                ->whereIn('inventory_location_id', $labLocationIds)
                ->where('is_active', true)
                ->get(['inventory_item_id', 'minimum_stock_level'])
            : collect();

        $stockLevels = is_string($activeBranchId) && $activeBranchId !== '' && $labLocationIds !== []
            ? StockMovement::query()
                ->where('branch_id', $activeBranchId)
                ->whereIn('inventory_location_id', $labLocationIds)
                ->select('inventory_item_id')
                ->selectRaw('SUM(quantity) as total_qty')
                ->groupBy('inventory_item_id')
                ->get()
                ->mapWithKeys(static fn (StockMovement $stockLevel): array => [
                    $stockLevel->inventory_item_id => (float) $stockLevel->total_qty,
                ])
            : collect();

        $minimumLevels = $labLocationItems
            ->groupBy('inventory_item_id')
            ->map(static fn ($items): float => (float) $items->sum('minimum_stock_level'));

        $stockItemIds = $labLocationItems
            ->pluck('inventory_item_id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values();

        $stockMetrics = [
            [
                'label' => 'Out of Stock',
                'value' => $stockItemIds
                    ->filter(static fn (string $id): bool => (float) ($stockLevels[$id] ?? 0) <= 0)
                    ->count(),
                'hint' => 'Lab stock items with no available quantity right now.',
            ],
            [
                'label' => 'Low Stock',
                'value' => $stockItemIds
                    ->filter(function (string $id) use ($stockLevels, $minimumLevels): bool {
                        $quantity = (float) ($stockLevels[$id] ?? 0);
                        $minimum = (float) ($minimumLevels[$id] ?? 0);

                        return $quantity > 0 && $quantity <= $minimum;
                    })
                    ->count(),
                'hint' => 'Items already at or below the minimum level.',
            ],
            [
                'label' => 'Expiring Soon',
                'value' => is_string($activeBranchId) && $activeBranchId !== '' && $labLocationIds !== []
                    ? InventoryBatch::query()
                        ->where('branch_id', $activeBranchId)
                        ->whereIn('inventory_location_id', $labLocationIds)
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>', now())
                        ->whereDate('expiry_date', '<=', now()->addDays(30))
                        ->distinct()
                        ->count('inventory_item_id')
                    : 0,
                'hint' => 'Lab stock items with batches expiring in the next 30 days.',
            ],
            [
                'label' => 'Expired Stock',
                'value' => is_string($activeBranchId) && $activeBranchId !== '' && $labLocationIds !== []
                    ? InventoryBatch::query()
                        ->where('branch_id', $activeBranchId)
                        ->whereIn('inventory_location_id', $labLocationIds)
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<', now())
                        ->distinct()
                        ->count('inventory_item_id')
                    : 0,
                'hint' => 'Lab stock items with at least one expired batch.',
            ],
        ];

        $requestStatusCounts = collect(LabRequestStatus::cases())
            ->map(fn (LabRequestStatus $status): array => [
                'label' => $status->label(),
                'value' => $status->value,
                'count' => (clone $requestQuery)->where('status', $status->value)->count(),
            ])
            ->values()
            ->all();

        $workflowStageCounts = [
            [
                'label' => 'Pending',
                'value' => 'pending',
                'count' => $this->itemQuery()->whereNull('received_at')->where('status', 'pending')->count(),
            ],
            [
                'label' => 'Sample Picked',
                'value' => 'sample_collected',
                'count' => $this->itemQuery()
                    ->whereNotNull('received_at')
                    ->whereNull('result_entered_at')
                    ->count(),
            ],
            [
                'label' => 'Result Entered',
                'value' => 'result_entered',
                'count' => $this->itemQuery()
                    ->whereNotNull('result_entered_at')
                    ->whereNull('reviewed_at')
                    ->whereNull('approved_at')
                    ->count(),
            ],
            [
                'label' => 'Reviewed',
                'value' => 'reviewed',
                'count' => $this->itemQuery()
                    ->whereNotNull('reviewed_at')
                    ->whereNull('approved_at')
                    ->count(),
            ],
            [
                'label' => 'Approved',
                'value' => 'approved',
                'count' => $this->itemQuery()->whereNotNull('approved_at')->count(),
            ],
        ];

        $recentRequests = $this->activeBranchWorkspace->apply(LabRequest::query())
            ->with([
                'requestedBy:id,first_name,last_name',
                'visit:id,visit_number,patient_id',
                'visit.patient:id,patient_number,first_name,last_name',
                'items' => static fn (HasMany $query): HasMany => $query
                    ->with([
                        'test:id,test_code,test_name,lab_test_category_id,result_type_id',
                        'test.labCategory:id,name',
                        'test.specimenTypes:id,name',
                        'test.resultTypeDefinition:id,code,name',
                    ])->oldest(),
            ])
            ->orderByRaw("case when priority in ('critical', 'stat', 'urgent') then 0 else 1 end")
            ->latest('request_date')
            ->limit(8)
            ->get();

        return Inertia::render('laboratory/dashboard', [
            'metrics' => $metrics,
            'stock_metrics' => $stockMetrics,
            'request_status_counts' => $requestStatusCounts,
            'workflow_stage_counts' => $workflowStageCounts,
            'recent_requests' => $recentRequests,
        ]);
    }

    private function itemQuery(): Builder
    {
        return LabRequestItem::query()->whereHas(
            'request',
            fn (Builder $query): Builder => $this->activeBranchWorkspace->apply($query),
        );
    }
}
