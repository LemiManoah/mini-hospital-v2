<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveInventoryRequisition;
use App\Actions\CancelInventoryRequisition;
use App\Actions\CreateInventoryRequisition;
use App\Actions\IssueInventoryRequisition;
use App\Actions\RejectInventoryRequisition;
use App\Actions\SubmitInventoryRequisition;
use App\Enums\InventoryRequisitionStatus;
use App\Enums\Priority;
use App\Enums\StockMovementType;
use App\Http\Requests\ApproveInventoryRequisitionRequest;
use App\Http\Requests\IssueInventoryRequisitionRequest;
use App\Http\Requests\StoreInventoryRequisitionRequest;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryRequisition;
use App\Models\InventoryRequisitionItem;
use App\Models\StockMovement;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryRequisitionAccess;
use App\Support\InventoryRequisitionWorkflow;
use App\Support\InventoryStockLedger;
use App\Support\InventoryWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryRequisitionController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryRequisitionAccess $inventoryRequisitionAccess,
        private InventoryRequisitionWorkflow $inventoryRequisitionWorkflow,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_requisitions.view', only: ['index', 'show']),
            new Middleware('permission:inventory_requisitions.create', only: ['create', 'store']),
            new Middleware('permission:inventory_requisitions.submit', only: ['submit']),
            new Middleware('permission:inventory_requisitions.cancel', only: ['cancel']),
            new Middleware('permission:inventory_requisitions.review', only: ['approve', 'reject']),
            new Middleware('permission:inventory_requisitions.issue', only: ['issue']),
        ];
    }

    public function index(Request $request): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));
        $activeBranchId = BranchContext::getActiveBranchId();
        $isIncomingQueue = $workspace->isInventory();
        $locationIds = $this->inventoryRequisitionAccess->indexLocationIds(
            Auth::user(),
            $workspace,
            $activeBranchId,
        );

        $requisitions = InventoryRequisition::query()
            ->with([
                'fulfillingLocation:id,name,location_code',
                'requestingLocation:id,name,location_code',
            ])
            ->when(
                $locationIds === [],
                static fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                fn (Builder $query): Builder => $query->when(
                    $isIncomingQueue,
                    fn (Builder $incomingQuery): Builder => tap(
                        $incomingQuery,
                        fn (Builder $queueQuery) => $this->inventoryRequisitionWorkflow
                            ->applyIncomingQueueScope($queueQuery, $locationIds),
                    ),
                    static function (Builder $workspaceQuery) use ($locationIds): void {
                        $workspaceQuery->where(function (Builder $inner) use ($locationIds): void {
                            $inner
                                ->whereIn('source_inventory_location_id', $locationIds)
                                ->orWhereIn('destination_inventory_location_id', $locationIds);
                        });
                    },
                ),
            )
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('requisition_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('fulfillingLocation', static fn (Builder $locationQuery) => $locationQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('requestingLocation', static fn (Builder $locationQuery) => $locationQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->latest('requisition_date')
            ->paginate(10)
            ->through(fn (InventoryRequisition $requisition): array => $this->serializeSummary($requisition))
            ->withQueryString();

        return Inertia::render($workspace->requisitionIndexComponent(), [
            'requisitions' => $requisitions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'statusOptions' => collect(InventoryRequisitionStatus::cases())
                ->map(static fn (InventoryRequisitionStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isRequester(), 404);

        $branchId = BranchContext::getActiveBranchId();
        $fulfillingLocations = $this->inventoryRequisitionAccess->fulfillingLocations(Auth::user(), $branchId);
        $requestingLocations = $this->inventoryRequisitionAccess->requestingLocations(Auth::user(), $workspace, $branchId);
        $fulfillingLocationIds = $fulfillingLocations
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        return Inertia::render($workspace->requisitionCreateComponent(), [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'fulfillingInventoryLocations' => $fulfillingLocations
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                ])
                ->values(),
            'requestingInventoryLocations' => $requestingLocations
                ->map(static fn (InventoryLocation $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_code' => $location->location_code,
                ])
                ->values(),
            'inventoryItems' => InventoryItem::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'item_type']),
            'sourceLocationBalances' => is_string($branchId) && $branchId !== ''
                ? $this->inventoryStockLedger
                    ->summarizeByLocation($branchId)
                    ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $fulfillingLocationIds, true))
                    ->values()
                : collect(),
            'priorityOptions' => collect(Priority::cases())
                ->map(static fn (Priority $priority): array => [
                    'value' => $priority->value,
                    'label' => $priority->label(),
                ])
                ->all(),
        ]);
    }

    public function store(StoreInventoryRequisitionRequest $request, CreateInventoryRequisition $action): RedirectResponse
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isRequester(), 404);

        $requisition = $action->handle($request->createDto(), $workspace->locationTypeValues());

        return to_route($workspace->requisitionShowRouteName(), $workspace->requisitionShowRouteParameters($requisition))
            ->with('success', 'Requisition created. Submit it when you are ready for main store review.');
    }

    public function show(Request $request, InventoryRequisition $requisition): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        $this->abortUnlessCanView($requisition, $workspace);

        $requisition->load([
            'fulfillingLocation',
            'requestingLocation',
            'items.inventoryItem',
        ]);

        $this->abortUnlessMatchesWorkspace($requisition, $workspace);

        return Inertia::render($workspace->requisitionShowComponent(), [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'requisition' => $this->serializeDetail($requisition, $this->issueHistory($requisition)),
            'availableBatchBalances' => $this->availableBatchBalances($requisition),
        ]);
    }

    public function submit(Request $request, InventoryRequisition $requisition, SubmitInventoryRequisition $action): RedirectResponse
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isRequester(), 404);

        $this->abortUnlessCanView($requisition, $workspace);

        $action->handle($requisition);

        return to_route($workspace->requisitionShowRouteName(), $workspace->requisitionShowRouteParameters($requisition))
            ->with('success', 'Requisition submitted to main store.');
    }

    public function cancel(
        Request $request,
        InventoryRequisition $requisition,
        CancelInventoryRequisition $action,
    ): RedirectResponse {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isRequester(), 404);

        $this->abortUnlessCanView($requisition, $workspace);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string'],
        ]);

        /** @var array{cancellation_reason: string} $validated */
        $action->handle($requisition, $validated['cancellation_reason']);

        return to_route($workspace->requisitionShowRouteName(), $workspace->requisitionShowRouteParameters($requisition))
            ->with('success', 'Requisition cancelled.');
    }

    public function approve(
        ApproveInventoryRequisitionRequest $request,
        InventoryRequisition $requisition,
        ApproveInventoryRequisition $action,
    ): RedirectResponse {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isInventory(), 404);

        $this->abortUnlessIncomingQueueItem($requisition);
        $this->abortUnlessCanProcess($requisition);

        $action->handle(
            $requisition,
            $request->approveDto(),
        );

        return to_route($workspace->requisitionShowRouteName(), $workspace->requisitionShowRouteParameters($requisition))
            ->with('success', 'Requisition approved and ready for issue.');
    }

    public function reject(Request $request, InventoryRequisition $requisition, RejectInventoryRequisition $action): RedirectResponse
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isInventory(), 404);

        $this->abortUnlessIncomingQueueItem($requisition);
        $this->abortUnlessCanProcess($requisition);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        /** @var array{rejection_reason: string} $validated */
        $action->handle($requisition, $validated['rejection_reason']);

        return to_route($workspace->requisitionShowRouteName(), $workspace->requisitionShowRouteParameters($requisition))
            ->with('success', 'Requisition rejected.');
    }

    public function issue(
        IssueInventoryRequisitionRequest $request,
        InventoryRequisition $requisition,
        IssueInventoryRequisition $action,
    ): RedirectResponse {
        $workspace = InventoryWorkspace::fromRequest($request);
        abort_unless($workspace->isInventory(), 404);

        $this->abortUnlessIncomingQueueItem($requisition);
        $this->abortUnlessCanProcess($requisition);

        $action->handle(
            $requisition,
            $request->issueDto(),
        );

        return to_route($workspace->requisitionShowRouteName(), $workspace->requisitionShowRouteParameters($requisition))
            ->with('success', 'Requisition issue posted successfully.');
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $issueHistory
     * @return array<string, mixed>
     */
    private function serializeDetail(InventoryRequisition $requisition, array $issueHistory): array
    {
        $fulfillingLocation = $requisition->fulfillingLocation === null ? null : [
            'id' => $requisition->fulfillingLocation->id,
            'name' => $requisition->fulfillingLocation->name,
            'location_code' => $requisition->fulfillingLocation->location_code,
        ];
        $requestingLocation = $requisition->requestingLocation === null ? null : [
            'id' => $requisition->requestingLocation->id,
            'name' => $requisition->requestingLocation->name,
            'location_code' => $requisition->requestingLocation->location_code,
        ];

        return [
            'id' => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'status' => $requisition->status->value,
            'status_label' => $requisition->status->label(),
            'priority' => $requisition->priority->value,
            'priority_label' => $requisition->priority->label(),
            'requisition_date' => $requisition->requisition_date->toDateString(),
            'notes' => $requisition->notes,
            'approval_notes' => $requisition->approval_notes,
            'rejection_reason' => $requisition->rejection_reason,
            'cancellation_reason' => $requisition->cancellation_reason,
            'issued_notes' => $requisition->issued_notes,
            'submitted_at' => $requisition->submitted_at?->toIso8601String(),
            'approved_at' => $requisition->approved_at?->toIso8601String(),
            'rejected_at' => $requisition->rejected_at?->toIso8601String(),
            'cancelled_at' => $requisition->cancelled_at?->toIso8601String(),
            'issued_at' => $requisition->issued_at?->toIso8601String(),
            'can_submit' => $requisition->canBeSubmitted(),
            'can_cancel' => $requisition->canBeCancelled(),
            'can_approve' => $requisition->canBeApproved(),
            'can_reject' => $requisition->canBeRejected(),
            'can_issue' => $requisition->canBeIssued(),
            'fulfilling_location' => $fulfillingLocation,
            'requesting_location' => $requestingLocation,
            'items' => $requisition->items->map(
                static fn (InventoryRequisitionItem $item): array => [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'requested_quantity' => (float) $item->requested_quantity,
                    'approved_quantity' => (float) $item->approved_quantity,
                    'issued_quantity' => (float) $item->issued_quantity,
                    'remaining_quantity' => $item->remainingApprovedQuantity(),
                    'notes' => $item->notes,
                    'inventory_item' => $item->inventoryItem === null ? null : [
                        'id' => $item->inventoryItem->id,
                        'name' => $item->inventoryItem->name,
                        'generic_name' => $item->inventoryItem->generic_name,
                    ],
                    'issue_history' => $issueHistory[$item->id] ?? [],
                ],
            )->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSummary(InventoryRequisition $requisition): array
    {
        $fulfillingLocation = $requisition->fulfillingLocation === null ? null : [
            'id' => $requisition->fulfillingLocation->id,
            'name' => $requisition->fulfillingLocation->name,
            'location_code' => $requisition->fulfillingLocation->location_code,
        ];
        $requestingLocation = $requisition->requestingLocation === null ? null : [
            'id' => $requisition->requestingLocation->id,
            'name' => $requisition->requestingLocation->name,
            'location_code' => $requisition->requestingLocation->location_code,
        ];

        return [
            'id' => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'status' => $requisition->status->value,
            'status_label' => $requisition->status->label(),
            'priority' => $requisition->priority->value,
            'priority_label' => $requisition->priority->label(),
            'requisition_date' => $requisition->requisition_date->toDateString(),
            'fulfilling_location' => $fulfillingLocation,
            'requesting_location' => $requestingLocation,
            'issued_at' => $requisition->issued_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function availableBatchBalances(InventoryRequisition $requisition): array
    {
        if (! $requisition->canBeIssued()) {
            return [];
        }

        if (! $this->inventoryLocationAccess->canFulfillRequisition(Auth::user(), $requisition, $requisition->branch_id)) {
            return [];
        }

        $activeBranchId = BranchContext::getActiveBranchId();
        if (! is_string($activeBranchId) || $activeBranchId === '') {
            return [];
        }

        /** @var array<string, InventoryBatch> $batches */
        $batches = InventoryBatch::query()
            ->with('inventoryItem:id,name,generic_name')
            ->whereIn(
                'id',
                $this->inventoryStockLedger
                    ->summarizeByBatch($activeBranchId)
                    ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $requisition->source_inventory_location_id && $balance['quantity'] > 0)
                    ->pluck('inventory_batch_id'),
            )
            ->get()
            ->keyBy('id')
            ->all();

        return $this->inventoryStockLedger
            ->summarizeByBatch($activeBranchId)
            ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $requisition->source_inventory_location_id && $balance['quantity'] > 0)
            ->map(static function (array $balance) use ($batches): array {
                $batch = $batches[$balance['inventory_batch_id']] ?? null;
                $inventoryItem = $batch?->inventoryItem;

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'item_name' => $inventoryItem === null ? null : ($inventoryItem->generic_name ?? $inventoryItem->name),
                ];
            })
            ->values()
            ->all();
    }

    private function abortUnlessCanView(InventoryRequisition $requisition, InventoryWorkspace $workspace): void
    {
        $user = Auth::user();

        abort_unless(
            $this->inventoryRequisitionAccess->canView($user, $requisition, $workspace),
            403,
            'You do not have access to this requisition.',
        );
    }

    private function abortUnlessCanProcess(InventoryRequisition $requisition): void
    {
        abort_unless(
            $this->inventoryRequisitionAccess->canProcess(Auth::user(), $requisition),
            403,
            'You can only process requisitions for inventory locations you manage.',
        );
    }

    private function abortUnlessMatchesWorkspace(InventoryRequisition $requisition, InventoryWorkspace $workspace): void
    {
        abort_unless(
            $this->inventoryRequisitionAccess->matchesWorkspace($requisition, $workspace),
            404,
            'This requisition does not belong to the selected inventory workspace.',
        );
    }

    private function abortUnlessIncomingQueueItem(InventoryRequisition $requisition, int $status = 403): void
    {
        abort_unless(
            $this->inventoryRequisitionAccess->isIncomingQueueItem($requisition),
            $status,
            'This requisition is not available in the main store queue.',
        );
    }

    /**
     * @return array<string, array<int, array{
     *   quantity: float,
     *   batch_number: string|null,
     *   expiry_date: string|null,
     *   occurred_at: string
     * }>>
     */
    private function issueHistory(InventoryRequisition $requisition): array
    {
        /** @var array<string, array<int, array{
         *   quantity: float,
         *   batch_number: string|null,
         *   expiry_date: string|null,
         *   occurred_at: string
         * }>> $history
         */
        $history = [];

        $movementsByLine = StockMovement::query()
            ->with('inventoryBatch:id,batch_number,expiry_date')
            ->where('source_document_type', InventoryRequisition::class)
            ->where('source_document_id', $requisition->id)
            ->where('movement_type', StockMovementType::RequisitionOut)
            ->oldest('occurred_at')
            ->get()
            ->groupBy('source_line_id');

        foreach ($movementsByLine as $sourceLineId => $movements) {
            if (! is_string($sourceLineId)) {
                continue;
            }

            if ($sourceLineId === '') {
                continue;
            }

            $history[$sourceLineId] = $movements
                ->map(static fn (StockMovement $movement): array => [
                    'quantity' => abs((float) $movement->quantity),
                    'batch_number' => $movement->inventoryBatch?->batch_number,
                    'expiry_date' => $movement->inventoryBatch?->expiry_date?->toDateString(),
                    'occurred_at' => $movement->occurred_at->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        return $history;
    }
}
