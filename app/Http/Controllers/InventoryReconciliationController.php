<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveInventoryReconciliation;
use App\Actions\CreateInventoryReconciliation;
use App\Actions\PostInventoryReconciliation;
use App\Actions\RejectInventoryReconciliation;
use App\Actions\ReviewInventoryReconciliation;
use App\Actions\SubmitInventoryReconciliation;
use App\Enums\StockAdjustmentStatus;
use App\Http\Requests\StoreInventoryReconciliationRequest;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Support\BranchContext;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryReconciliationController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock_adjustments.view', only: ['index', 'show']),
            new Middleware('permission:stock_adjustments.create', only: ['create', 'store']),
            new Middleware('permission:stock_adjustments.update', only: ['submit', 'review', 'approve', 'reject', 'post']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $reconciliations = StockAdjustment::query()
            ->with('inventoryLocation:id,name,location_code')
            ->when(
                $search !== '',
                static function (Builder $query) use ($search): void {
                    $query->where(function (Builder $inner) use ($search): void {
                        $inner
                            ->where('adjustment_number', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('reason', 'like', sprintf('%%%s%%', $search));
                    });
                },
            )
            ->when(
                $status !== '',
                fn (Builder $query) => $this->applyWorkflowStatusFilter($query, $status),
            )
            ->latest('adjustment_date')
            ->paginate(10)
            ->through(fn (StockAdjustment $reconciliation): array => $this->serializeReconciliationSummary($reconciliation))
            ->withQueryString();

        return Inertia::render('inventory/reconciliations/index', [
            'reconciliations' => $reconciliations,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $activeBranchId = BranchContext::getActiveBranchId();

        $locationBalances = is_string($activeBranchId) && $activeBranchId !== ''
            ? $this->inventoryStockLedger
                ->summarizeByLocation($activeBranchId)
                ->values()
            : collect();

        $batchBalances = is_string($activeBranchId) && $activeBranchId !== ''
            ? $this->inventoryStockLedger
                ->summarizeByBatch($activeBranchId)
                ->filter(static fn (array $balance): bool => $balance['quantity'] > 0)
                ->values()
            : collect();

        /** @var array<string, InventoryBatch> $batches */
        $batches = InventoryBatch::query()
            ->with([
                'inventoryItem:id,name,generic_name',
                'inventoryLocation:id,name,location_code',
            ])
            ->whereIn('id', $batchBalances->pluck('inventory_batch_id'))
            ->get()
            ->keyBy('id')
            ->all();

        return Inertia::render('inventory/reconciliations/create', [
            'inventoryLocations' => InventoryLocation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location_code']),
            'inventoryItems' => InventoryItem::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'item_type', 'default_purchase_price']),
            'locationBalances' => $locationBalances->map(static fn (array $balance): array => [
                'inventory_location_id' => $balance['inventory_location_id'],
                'inventory_item_id' => $balance['inventory_item_id'],
                'quantity' => $balance['quantity'],
            ])->values(),
            'batchBalances' => $batchBalances->map(static function (array $balance) use ($batches): array {
                $batch = $batches[$balance['inventory_batch_id']] ?? null;

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_location_id' => $balance['inventory_location_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'item_name' => $batch?->inventoryItem?->generic_name ?? $batch?->inventoryItem?->name,
                    'location_name' => $batch?->inventoryLocation?->name,
                ];
            })->values(),
        ]);
    }

    public function store(StoreInventoryReconciliationRequest $request, CreateInventoryReconciliation $action): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $reconciliation = $action->handle($validated, $items);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation created successfully.');
    }

    public function show(StockAdjustment $reconciliation): Response
    {
        $reconciliation->load([
            'inventoryLocation',
            'items.inventoryItem',
            'items.inventoryBatch',
        ]);

        return Inertia::render('inventory/reconciliations/show', [
            'reconciliation' => $this->serializeReconciliationDetail($reconciliation),
        ]);
    }

    public function submit(StockAdjustment $reconciliation, SubmitInventoryReconciliation $action): RedirectResponse
    {
        $action->handle($reconciliation);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation submitted for review.');
    }

    public function review(Request $request, StockAdjustment $reconciliation, ReviewInventoryReconciliation $action): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string'],
        ]);

        $action->handle($reconciliation, $validated['review_notes'] ?? null);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation reviewed.');
    }

    public function approve(Request $request, StockAdjustment $reconciliation, ApproveInventoryReconciliation $action): RedirectResponse
    {
        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string'],
        ]);

        $action->handle($reconciliation, $validated['approval_notes'] ?? null);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation approved and ready to post.');
    }

    public function reject(Request $request, StockAdjustment $reconciliation, RejectInventoryReconciliation $action): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $action->handle($reconciliation, $validated['rejection_reason']);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation rejected.');
    }

    public function post(StockAdjustment $reconciliation, PostInventoryReconciliation $action): RedirectResponse
    {
        $action->handle($reconciliation);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation posted. Inventory balances updated.');
    }

    private function applyWorkflowStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'draft' => $query
                ->where('status', StockAdjustmentStatus::Draft)
                ->whereNull('submitted_at')
                ->whereNull('rejected_at'),
            'submitted' => $query
                ->where('status', StockAdjustmentStatus::Draft)
                ->whereNotNull('submitted_at')
                ->whereNull('reviewed_at')
                ->whereNull('rejected_at'),
            'reviewed' => $query
                ->where('status', StockAdjustmentStatus::Draft)
                ->whereNotNull('reviewed_at')
                ->whereNull('approved_at')
                ->whereNull('rejected_at'),
            'approved' => $query
                ->where('status', StockAdjustmentStatus::Draft)
                ->whereNotNull('approved_at')
                ->whereNull('rejected_at'),
            'rejected' => $query
                ->where('status', StockAdjustmentStatus::Draft)
                ->whereNotNull('rejected_at'),
            'posted' => $query->where('status', StockAdjustmentStatus::Posted),
            default => $query,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'reviewed', 'label' => 'Reviewed'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'posted', 'label' => 'Posted'],
            ['value' => 'rejected', 'label' => 'Rejected'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReconciliationSummary(StockAdjustment $reconciliation): array
    {
        return [
            'id' => $reconciliation->id,
            'adjustment_number' => $reconciliation->adjustment_number,
            'workflow_status' => $reconciliation->workflowStatus(),
            'adjustment_date' => $reconciliation->adjustment_date?->toDateString(),
            'reason' => $reconciliation->reason,
            'posted_at' => $reconciliation->posted_at?->toIso8601String(),
            'inventory_location' => $reconciliation->inventoryLocation === null ? null : [
                'id' => $reconciliation->inventoryLocation->id,
                'name' => $reconciliation->inventoryLocation->name,
                'location_code' => $reconciliation->inventoryLocation->location_code,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReconciliationDetail(StockAdjustment $reconciliation): array
    {
        return [
            'id' => $reconciliation->id,
            'adjustment_number' => $reconciliation->adjustment_number,
            'workflow_status' => $reconciliation->workflowStatus(),
            'adjustment_date' => $reconciliation->adjustment_date?->toDateString(),
            'reason' => $reconciliation->reason,
            'notes' => $reconciliation->notes,
            'review_notes' => $reconciliation->review_notes,
            'approval_notes' => $reconciliation->approval_notes,
            'rejection_reason' => $reconciliation->rejection_reason,
            'submitted_at' => $reconciliation->submitted_at?->toIso8601String(),
            'reviewed_at' => $reconciliation->reviewed_at?->toIso8601String(),
            'approved_at' => $reconciliation->approved_at?->toIso8601String(),
            'rejected_at' => $reconciliation->rejected_at?->toIso8601String(),
            'posted_at' => $reconciliation->posted_at?->toIso8601String(),
            'can_submit' => $reconciliation->canBeSubmitted(),
            'can_review' => $reconciliation->canBeReviewed(),
            'can_approve' => $reconciliation->canBeApproved(),
            'can_reject' => $reconciliation->canBeRejected(),
            'can_post' => $reconciliation->canBePosted(),
            'inventory_location' => $reconciliation->inventoryLocation === null ? null : [
                'id' => $reconciliation->inventoryLocation->id,
                'name' => $reconciliation->inventoryLocation->name,
                'location_code' => $reconciliation->inventoryLocation->location_code,
            ],
            'items' => $reconciliation->items->map(static fn (StockAdjustmentItem $item): array => [
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'inventory_batch_id' => $item->inventory_batch_id,
                'expected_quantity' => $item->expected_quantity,
                'actual_quantity' => $item->actual_quantity,
                'variance_quantity' => $item->variance_quantity ?? $item->quantity_delta,
                'quantity_delta' => $item->quantity_delta,
                'unit_cost' => $item->unit_cost,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date?->toDateString(),
                'notes' => $item->notes,
                'inventory_item' => $item->inventoryItem === null ? null : [
                    'id' => $item->inventoryItem->id,
                    'name' => $item->inventoryItem->name,
                    'generic_name' => $item->inventoryItem->generic_name,
                ],
                'inventory_batch' => $item->inventoryBatch === null ? null : [
                    'id' => $item->inventoryBatch->id,
                    'batch_number' => $item->inventoryBatch->batch_number,
                    'expiry_date' => $item->inventoryBatch->expiry_date?->toDateString(),
                ],
            ])->all(),
        ];
    }
}
