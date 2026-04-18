<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteLabRequestItemConsumable;
use App\Actions\RecordLabRequestItemConsumable;
use App\Enums\InventoryLocationType;
use App\Http\Requests\StoreLabRequestItemConsumableRequest;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\LabRequestItem;
use App\Models\LabRequestItemConsumable;
use App\Support\ActiveBranchWorkspace;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LabRequestItemConsumableController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_requests.view', only: ['show']),
            new Middleware('permission:lab_requests.update', only: ['store', 'destroy']),
        ];
    }

    public function show(LabRequestItem $labRequestItem): Response
    {
        $labRequest = $labRequestItem->request()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labRequest);

        $labRequestItem->load([
            'request:id,visit_id,facility_branch_id,requested_by,request_date,priority,status,clinical_notes,tenant_id',
            'request.requestedBy:id,first_name,last_name',
            'request.visit:id,visit_number,patient_id',
            'request.visit.patient:id,patient_number,first_name,last_name,gender,phone_number',
            'test:id,test_code,test_name,description,lab_test_category_id,result_type_id,base_price',
            'test.labCategory:id,name',
            'test.resultTypeDefinition:id,code,name',
            'consumables' => static fn (HasMany $query): HasMany => $query
                ->with('recordedBy:id,first_name,last_name')
                ->latest('used_at'),
        ]);

        $laboratoryLocationIds = InventoryLocation::query()
            ->where('tenant_id', $labRequest->tenant_id)
            ->where('branch_id', $labRequest->facility_branch_id)
            ->where('type', InventoryLocationType::LABORATORY)
            ->where('is_active', true)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        $availableBalances = $this->inventoryStockLedger
            ->summarizeByLocation($labRequest->facility_branch_id)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $laboratoryLocationIds, true))
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $balances): float => (float) collect($balances)->sum('quantity'));

        $consumableOptions = InventoryItem::query()
            ->where('tenant_id', $labRequest->tenant_id)
            ->active()
            ->with('unit:id,name,symbol')
            ->orderBy('name')
            ->get(['id', 'tenant_id', 'name', 'item_type', 'unit_id', 'default_purchase_price'])
            ->map(static fn (InventoryItem $inventoryItem) => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'item_type' => $inventoryItem->item_type?->value,
                'label' => sprintf(
                    '%s | Qty %.3f%s',
                    $inventoryItem->name,
                    (float) ($availableBalances->get($inventoryItem->id) ?? 0),
                    $inventoryItem->unit?->symbol ? ' '.$inventoryItem->unit->symbol : '',
                ),
                'unit_label' => $inventoryItem->unit?->symbol ?: $inventoryItem->unit?->name,
                'default_unit_cost' => $inventoryItem->default_purchase_price !== null
                    ? (float) $inventoryItem->default_purchase_price
                    : null,
                'available_quantity' => (float) ($availableBalances->get($inventoryItem->id) ?? 0),
            ])
            ->values()
            ->all();

        return Inertia::render('laboratory/request-item-consumables', [
            'labRequestItem' => $labRequestItem,
            'consumableOptions' => $consumableOptions,
        ]);
    }

    public function store(
        StoreLabRequestItemConsumableRequest $request,
        LabRequestItem $labRequestItem,
        RecordLabRequestItemConsumable $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($labRequestItem->request);

        $staffId = $request->user()?->staff_id;

        if ($staffId === null) {
            return to_route('laboratory.request-items.consumables.show', $labRequestItem)
                ->with('error', 'Consumable usage requires a linked staff profile for audit tracking.');
        }

        $action->handle($labRequestItem->loadMissing('request'), $request->validated(), $staffId);

        return to_route('laboratory.request-items.consumables.show', $labRequestItem)
            ->with('success', 'Consumable usage recorded successfully.');
    }

    public function destroy(
        LabRequestItem $labRequestItem,
        LabRequestItemConsumable $labRequestItemConsumable,
        DeleteLabRequestItemConsumable $action,
    ): RedirectResponse {
        $this->activeBranchWorkspace->authorizeModel($labRequestItem->request);

        abort_unless($labRequestItemConsumable->lab_request_item_id === $labRequestItem->id, 404);

        $action->handle($labRequestItemConsumable);

        return to_route('laboratory.request-items.consumables.show', $labRequestItem)
            ->with('success', 'Consumable usage removed successfully.');
    }
}
