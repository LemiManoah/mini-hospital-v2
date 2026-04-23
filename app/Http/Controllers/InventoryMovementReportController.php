<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\StockMovement;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryMovementReportController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $workspace = InventoryWorkspace::fromRequest($request);
        $activeBranchId = BranchContext::getActiveBranchId();
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));
        $locationId = mb_trim((string) $request->query('location', ''));
        $accessibleLocations = $this->inventoryLocationAccess->accessibleLocations(Auth::user(), $activeBranchId, $workspace->locationTypeValues());
        $locationIds = $accessibleLocations
            ->pluck('id')
            ->filter(static fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        $query = StockMovement::query()
            ->with([
                'inventoryItem:id,name,generic_name',
                'inventoryLocation:id,name,location_code',
                'inventoryBatch:id,batch_number,expiry_date',
            ])
            ->latest('occurred_at')->latest();

        if (is_string($activeBranchId) && $activeBranchId !== '' && $locationIds !== []) {
            $query
                ->where('branch_id', $activeBranchId)
                ->whereIn('inventory_location_id', $locationIds);
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
            ->through(static function (StockMovement $movement): array {
                $inventoryItem = $movement->inventoryItem;

                return [
                    'id' => $movement->id,
                    'item_name' => $inventoryItem instanceof InventoryItem
                        ? ($inventoryItem->generic_name ?? $inventoryItem->name)
                        : null,
                    'location_name' => $movement->inventoryLocation?->name,
                    'location_code' => $movement->inventoryLocation?->location_code,
                    'movement_type' => $movement->movement_type->value,
                    'movement_type_label' => $movement->movement_type->label(),
                    'quantity' => (float) $movement->quantity,
                    'unit_cost' => $movement->unit_cost !== null ? (float) $movement->unit_cost : null,
                    'batch_number' => $movement->inventoryBatch?->batch_number,
                    'expiry_date' => $movement->inventoryBatch?->expiry_date?->toDateString(),
                    'occurred_at' => $movement->occurred_at->toIso8601String(),
                ];
            });

        $locations = $accessibleLocations
            ->map(static fn (InventoryLocation $location): array => [
                'value' => $location->id,
                'label' => sprintf('%s (%s)', $location->name, $location->location_code),
            ])
            ->values();

        return Inertia::render($workspace->movementsComponent(), [
            'movements' => $movements,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'type' => $type !== '' ? $type : null,
                'location' => $locationId !== '' ? $locationId : null,
            ],
            'navigation' => InventoryNavigationContext::fromRequest($request),
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
