<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveInventoryReconciliation;
use App\Actions\CreateInventoryReconciliation;
use App\Actions\PostInventoryReconciliation;
use App\Actions\RejectInventoryReconciliation;
use App\Actions\ReviewInventoryReconciliation;
use App\Actions\SubmitInventoryReconciliation;
use App\Enums\ReconciliationStatus;
use App\Http\Requests\StoreInventoryReconciliationRequest;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryReconciliationController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
        private InventoryLocationAccess $inventoryLocationAccess,
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
        $locationIds = $this->inventoryLocationAccess->accessibleLocationIds(Auth::user(), BranchContext::getActiveBranchId());

        $reconciliations = Reconciliation::query()
            ->with('inventoryLocation:id,name,location_code')
            ->when(
                $locationIds === [],
                static fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                static fn (Builder $query): Builder => $query->whereIn('inventory_location_id', $locationIds),
            )
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
            ->through(fn (Reconciliation $reconciliation): array => $this->serializeReconciliationSummary($reconciliation))
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
        $locationIds = $this->inventoryLocationAccess->accessibleLocationIds(Auth::user(), $activeBranchId);

        $locationBalances = is_string($activeBranchId) && $activeBranchId !== ''
            ? $this->inventoryStockLedger
                ->summarizeByLocation($activeBranchId)
                ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
                ->values()
            : collect();

        $batchBalances = is_string($activeBranchId) && $activeBranchId !== ''
            ? $this->inventoryStockLedger
                ->summarizeByBatch($activeBranchId)
                ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $locationIds, true))
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
            'inventoryLocations' => $this->inventoryLocationAccess
                ->accessibleLocations(Auth::user(), $activeBranchId)
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                ])
                ->values(),
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
            ->with('success', 'Reconciliation created successfully.')
            ->with('reconciliation_prompt', 'submit');
    }

    public function show(Reconciliation $reconciliation): Response
    {
        $this->abortUnlessCanAccess($reconciliation);

        $reconciliation->load([
            'inventoryLocation',
            'items.inventoryItem',
            'items.inventoryBatch',
        ]);

        return Inertia::render('inventory/reconciliations/show', [
            'reconciliation' => $this->serializeReconciliationDetail($reconciliation),
        ]);
    }

    public function submit(Reconciliation $reconciliation, SubmitInventoryReconciliation $action): RedirectResponse
    {
        $this->abortUnlessCanAccess($reconciliation);

        $action->handle($reconciliation);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation submitted for review.');
    }

    public function review(Request $request, Reconciliation $reconciliation, ReviewInventoryReconciliation $action): RedirectResponse
    {
        $this->abortUnlessCanAccess($reconciliation);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string'],
        ]);

        $action->handle($reconciliation, $validated['review_notes'] ?? null);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation reviewed.');
    }

    public function approve(Request $request, Reconciliation $reconciliation, ApproveInventoryReconciliation $action): RedirectResponse
    {
        $this->abortUnlessCanAccess($reconciliation);

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string'],
        ]);

        $action->handle($reconciliation, $validated['approval_notes'] ?? null);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation approved and ready to post.')
            ->with('reconciliation_prompt', 'post');
    }

    public function reject(Request $request, Reconciliation $reconciliation, RejectInventoryReconciliation $action): RedirectResponse
    {
        $this->abortUnlessCanAccess($reconciliation);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $action->handle($reconciliation, $validated['rejection_reason']);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation rejected.');
    }

    public function post(Reconciliation $reconciliation, PostInventoryReconciliation $action): RedirectResponse
    {
        $this->abortUnlessCanAccess($reconciliation);

        $action->handle($reconciliation);

        return to_route('reconciliations.show', $reconciliation)
            ->with('success', 'Reconciliation posted. Inventory balances updated.');
    }

    private function applyWorkflowStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'draft' => $query
                ->where('status', ReconciliationStatus::Draft)
                ->whereNull('submitted_at')
                ->whereNull('rejected_at'),
            'submitted' => $query
                ->where('status', ReconciliationStatus::Draft)
                ->whereNotNull('submitted_at')
                ->whereNull('reviewed_at')
                ->whereNull('rejected_at'),
            'reviewed' => $query
                ->where('status', ReconciliationStatus::Draft)
                ->whereNotNull('reviewed_at')
                ->whereNull('approved_at')
                ->whereNull('rejected_at'),
            'approved' => $query
                ->where('status', ReconciliationStatus::Draft)
                ->whereNotNull('approved_at')
                ->whereNull('rejected_at'),
            'rejected' => $query
                ->where('status', ReconciliationStatus::Draft)
                ->whereNotNull('rejected_at'),
            'posted' => $query->where('status', ReconciliationStatus::Posted),
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
    private function serializeReconciliationSummary(Reconciliation $reconciliation): array
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
    private function serializeReconciliationDetail(Reconciliation $reconciliation): array
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
            'items' => $reconciliation->items->map(static fn (ReconciliationItem $item): array => [
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

    private function abortUnlessCanAccess(Reconciliation $reconciliation): void
    {
        abort_unless(
            $this->inventoryLocationAccess->canAccessLocation(Auth::user(), $reconciliation->inventory_location_id, $reconciliation->branch_id),
            403,
            'You do not have access to this reconciliation.',
        );
    }
}
