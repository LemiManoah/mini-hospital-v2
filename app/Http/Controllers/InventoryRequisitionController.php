<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveInventoryRequisition;
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
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryRequisitionController implements HasMiddleware
{
    public function __construct(
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_requisitions.view', only: ['index', 'show']),
            new Middleware('permission:inventory_requisitions.create', only: ['create', 'store']),
            new Middleware('permission:inventory_requisitions.update', only: ['submit', 'approve', 'reject', 'issue']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $status = mb_trim((string) $request->query('status', ''));

        $requisitions = InventoryRequisition::query()
            ->with([
                'sourceLocation:id,name,location_code',
                'destinationLocation:id,name,location_code',
            ])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('requisition_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('sourceLocation', static fn (Builder $locationQuery) => $locationQuery->where('name', 'like', sprintf('%%%s%%', $search)))
                        ->orWhereHas('destinationLocation', static fn (Builder $locationQuery) => $locationQuery->where('name', 'like', sprintf('%%%s%%', $search)));
                });
            })
            ->when($status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->latest('requisition_date')
            ->paginate(10)
            ->through(fn (InventoryRequisition $requisition): array => $this->serializeSummary($requisition))
            ->withQueryString();

        return Inertia::render('inventory/requisitions/index', [
            'requisitions' => $requisitions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => collect(InventoryRequisitionStatus::cases())
                ->map(static fn (InventoryRequisitionStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory/requisitions/create', [
            'inventoryLocations' => InventoryLocation::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location_code']),
            'inventoryItems' => InventoryItem::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'generic_name', 'item_type']),
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
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $requisition = $action->handle($validated, $items);

        return to_route('inventory-requisitions.show', $requisition)
            ->with('success', 'Requisition created successfully.');
    }

    public function show(InventoryRequisition $requisition): Response
    {
        $requisition->load([
            'sourceLocation',
            'destinationLocation',
            'items.inventoryItem',
        ]);

        return Inertia::render('inventory/requisitions/show', [
            'requisition' => $this->serializeDetail($requisition, $this->issueHistory($requisition)),
            'availableBatchBalances' => $this->availableBatchBalances($requisition),
        ]);
    }

    public function submit(InventoryRequisition $requisition, SubmitInventoryRequisition $action): RedirectResponse
    {
        $action->handle($requisition);

        return to_route('inventory-requisitions.show', $requisition)
            ->with('success', 'Requisition submitted for approval.');
    }

    public function approve(
        ApproveInventoryRequisitionRequest $request,
        InventoryRequisition $requisition,
        ApproveInventoryRequisition $action,
    ): RedirectResponse {
        $validated = $request->validated();

        $action->handle(
            $requisition,
            $validated['items'],
            is_string($validated['approval_notes'] ?? null) ? $validated['approval_notes'] : null,
        );

        return to_route('inventory-requisitions.show', $requisition)
            ->with('success', 'Requisition approved and ready for issue.');
    }

    public function reject(Request $request, InventoryRequisition $requisition, RejectInventoryRequisition $action): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $action->handle($requisition, $validated['rejection_reason']);

        return to_route('inventory-requisitions.show', $requisition)
            ->with('success', 'Requisition rejected.');
    }

    public function issue(
        IssueInventoryRequisitionRequest $request,
        InventoryRequisition $requisition,
        IssueInventoryRequisition $action,
    ): RedirectResponse {
        $validated = $request->validated();

        $action->handle(
            $requisition,
            $validated['items'],
            is_string($validated['issued_notes'] ?? null) ? $validated['issued_notes'] : null,
        );

        return to_route('inventory-requisitions.show', $requisition)
            ->with('success', 'Requisition issue posted successfully.');
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $issueHistory
     * @return array<string, mixed>
     */
    private function serializeDetail(InventoryRequisition $requisition, array $issueHistory): array
    {
        return [
            'id' => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'status' => $requisition->status?->value,
            'status_label' => $requisition->status?->label(),
            'priority' => $requisition->priority?->value,
            'priority_label' => $requisition->priority?->label(),
            'requisition_date' => $requisition->requisition_date?->toDateString(),
            'notes' => $requisition->notes,
            'approval_notes' => $requisition->approval_notes,
            'rejection_reason' => $requisition->rejection_reason,
            'issued_notes' => $requisition->issued_notes,
            'submitted_at' => $requisition->submitted_at?->toIso8601String(),
            'approved_at' => $requisition->approved_at?->toIso8601String(),
            'rejected_at' => $requisition->rejected_at?->toIso8601String(),
            'issued_at' => $requisition->issued_at?->toIso8601String(),
            'can_submit' => $requisition->canBeSubmitted(),
            'can_approve' => $requisition->canBeApproved(),
            'can_reject' => $requisition->canBeRejected(),
            'can_issue' => $requisition->canBeIssued(),
            'source_location' => $requisition->sourceLocation === null ? null : [
                'id' => $requisition->sourceLocation->id,
                'name' => $requisition->sourceLocation->name,
                'location_code' => $requisition->sourceLocation->location_code,
            ],
            'destination_location' => $requisition->destinationLocation === null ? null : [
                'id' => $requisition->destinationLocation->id,
                'name' => $requisition->destinationLocation->name,
                'location_code' => $requisition->destinationLocation->location_code,
            ],
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
        return [
            'id' => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'status' => $requisition->status?->value,
            'status_label' => $requisition->status?->label(),
            'priority' => $requisition->priority?->value,
            'priority_label' => $requisition->priority?->label(),
            'requisition_date' => $requisition->requisition_date?->toDateString(),
            'source_location' => $requisition->sourceLocation === null ? null : [
                'id' => $requisition->sourceLocation->id,
                'name' => $requisition->sourceLocation->name,
                'location_code' => $requisition->sourceLocation->location_code,
            ],
            'destination_location' => $requisition->destinationLocation === null ? null : [
                'id' => $requisition->destinationLocation->id,
                'name' => $requisition->destinationLocation->name,
                'location_code' => $requisition->destinationLocation->location_code,
            ],
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

                return [
                    'inventory_batch_id' => $balance['inventory_batch_id'],
                    'inventory_item_id' => $balance['inventory_item_id'],
                    'batch_number' => $balance['batch_number'],
                    'expiry_date' => $balance['expiry_date'],
                    'quantity' => $balance['quantity'],
                    'item_name' => $batch?->inventoryItem?->generic_name ?? $batch?->inventoryItem?->name,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function issueHistory(InventoryRequisition $requisition): array
    {
        return StockMovement::query()
            ->with('inventoryBatch:id,batch_number,expiry_date')
            ->where('source_document_type', InventoryRequisition::class)
            ->where('source_document_id', $requisition->id)
            ->where('movement_type', StockMovementType::RequisitionOut)
            ->orderBy('occurred_at')
            ->get()
            ->groupBy('source_line_id')
            ->map(static fn (Collection $movements): array => $movements
                ->map(static fn (StockMovement $movement): array => [
                    'quantity' => abs((float) $movement->quantity),
                    'batch_number' => $movement->inventoryBatch?->batch_number,
                    'expiry_date' => $movement->inventoryBatch?->expiry_date?->toDateString(),
                    'occurred_at' => $movement->occurred_at?->toIso8601String(),
                ])
                ->values()
                ->all())
            ->all();
    }
}
