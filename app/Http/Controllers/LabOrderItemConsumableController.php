<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteLabOrderItemConsumable;
use App\Actions\RecordLabOrderItemConsumable;
use App\Enums\InventoryLocationType;
use App\Http\Requests\StoreLabOrderItemConsumableRequest;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\LabOrderItem;
use App\Models\LabOrderItemConsumable;
use App\Support\ActiveBranchWorkspace;
use App\Support\InventoryStockLedger;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LabOrderItemConsumableController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_orders.view', only: ['show']),
            new Middleware('permission:lab_orders.update', only: ['store', 'destroy']),
        ];
    }

    public function show(LabOrderItem $labOrderItem): Response
    {
        $labOrder = $labOrderItem->order()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labOrder);

        $labOrderItem->load([
            'order:id,visit_id,facility_branch_id,requested_by,request_date,priority,status,clinical_notes,tenant_id',
            'order.requestedBy:id,first_name,last_name',
            'order.visit:id,visit_number,patient_id',
            'order.visit.patient:id,patient_number,first_name,last_name,gender,phone_number',
            'test:id,test_code,test_name,description,lab_test_category_id,result_type_id,base_price',
            'test.labCategory:id,name',
            'test.resultTypeDefinition:id,code,name',
            'consumables' => static fn (HasMany $query): HasMany => $query
                ->with('recordedBy:id,first_name,last_name')
                ->latest('used_at'),
        ]);

        $laboratoryLocationIds = InventoryLocation::query()
            ->where('tenant_id', $labOrder->tenant_id)
            ->where('branch_id', $labOrder->facility_branch_id)
            ->where('type', InventoryLocationType::LABORATORY)
            ->where('is_active', true)
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        $availableBalances = $this->inventoryStockLedger
            ->summarizeByLocation($labOrder->facility_branch_id)
            ->filter(static fn (array $balance): bool => in_array($balance['inventory_location_id'], $laboratoryLocationIds, true))
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $balances): float => $balances->sum(
                static fn (array $balance): float => $balance['quantity'],
            ));

        $consumableOptions = InventoryItem::query()
            ->where('tenant_id', $labOrder->tenant_id)
            ->active()
            ->with('unit:id,name,symbol')
            ->orderBy('name')
            ->get(['id', 'tenant_id', 'name', 'item_type', 'unit_id', 'default_purchase_price'])
            ->map(static fn (InventoryItem $inventoryItem): array => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'item_type' => $inventoryItem->item_type->value,
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

        return Inertia::render('laboratory/order-item-consumables', [
            'labOrderItem' => $labOrderItem,
            'consumableOptions' => $consumableOptions,
        ]);
    }

    public function store(
        StoreLabOrderItemConsumableRequest $request,
        LabOrderItem $labOrderItem,
        RecordLabOrderItemConsumable $action,
    ): RedirectResponse {
        $labOrder = $labOrderItem->order()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labOrder);

        $staffId = $request->user()?->staff_id;

        if ($staffId === null) {
            return to_route('laboratory.order-items.consumables.show', $labOrderItem)
                ->with('error', 'Consumable usage requires a linked staff profile for audit tracking.');
        }

        $action->handle($labOrderItem->loadMissing('order'), $request->dto(), $staffId);

        return to_route('laboratory.order-items.consumables.show', $labOrderItem)
            ->with('success', 'Consumable usage recorded successfully.');
    }

    public function destroy(
        LabOrderItem $labOrderItem,
        LabOrderItemConsumable $labOrderItemConsumable,
        DeleteLabOrderItemConsumable $action,
    ): RedirectResponse {
        $labOrder = $labOrderItem->order()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labOrder);

        abort_unless($labOrderItemConsumable->lab_order_item_id === $labOrderItem->id, 404);

        $action->handle($labOrderItemConsumable);

        return to_route('laboratory.order-items.consumables.show', $labOrderItem)
            ->with('success', 'Consumable usage removed successfully.');
    }
}
