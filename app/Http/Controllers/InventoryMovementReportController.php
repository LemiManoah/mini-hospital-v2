<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\InventoryLocation;
use App\Models\StockMovement;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryMovementReportController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $activeBranchId = BranchContext::getActiveBranchId();
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));
        $locationId = mb_trim((string) $request->query('location', ''));

        $query = StockMovement::query()
            ->with([
                'inventoryItem:id,name,generic_name',
                'inventoryLocation:id,name,location_code',
                'inventoryBatch:id,batch_number,expiry_date',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if (is_string($activeBranchId) && $activeBranchId !== '') {
            $query->where('branch_id', $activeBranchId);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereHas('inventoryItem', function (Builder $itemQuery) use ($search): void {
                    $itemQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('generic_name', 'like', '%'.$search.'%');
                })->orWhereHas('inventoryLocation', function (Builder $locationQuery) use ($search): void {
                    $locationQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('location_code', 'like', '%'.$search.'%');
                });
            });
        }

        if ($type !== '') {
            $query->where('movement_type', $type);
        }

        if ($locationId !== '') {
            $query->where('inventory_location_id', $locationId);
        }

        $movements = $query
            ->paginate(20)
            ->through(static fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'item_name' => $movement->inventoryItem?->generic_name ?? $movement->inventoryItem?->name,
                'location_name' => $movement->inventoryLocation?->name,
                'location_code' => $movement->inventoryLocation?->location_code,
                'movement_type' => $movement->movement_type?->value,
                'movement_type_label' => $movement->movement_type?->label(),
                'quantity' => (float) $movement->quantity,
                'unit_cost' => $movement->unit_cost !== null ? (float) $movement->unit_cost : null,
                'batch_number' => $movement->inventoryBatch?->batch_number,
                'expiry_date' => $movement->inventoryBatch?->expiry_date?->toDateString(),
                'occurred_at' => $movement->occurred_at?->toIso8601String(),
            ]);

        $locations = InventoryLocation::query()
            ->where('branch_id', $activeBranchId)
            ->orderBy('name')
            ->get(['id', 'name', 'location_code'])
            ->map(static fn (InventoryLocation $location): array => [
                'value' => $location->id,
                'label' => sprintf('%s (%s)', $location->name, $location->location_code),
            ])
            ->values();

        return Inertia::render('inventory/reports/movements/index', [
            'movements' => $movements,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'type' => $type !== '' ? $type : null,
                'location' => $locationId !== '' ? $locationId : null,
            ],
            'movementTypes' => collect(StockMovementType::cases())
                ->map(static fn (StockMovementType $movementType): array => [
                    'value' => $movementType->value,
                    'label' => $movementType->label(),
                ])
                ->values(),
            'locations' => $locations,
        ]);
    }
}
