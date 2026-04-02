<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInventoryLocation;
use App\Actions\DeleteInventoryLocation;
use App\Actions\UpdateInventoryLocation;
use App\Enums\InventoryLocationType;
use App\Http\Requests\DeleteInventoryLocationRequest;
use App\Http\Requests\StoreInventoryLocationRequest;
use App\Http\Requests\UpdateInventoryLocationRequest;
use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryLocationController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_locations.view', only: ['index']),
            new Middleware('permission:inventory_locations.create', only: ['create', 'store']),
            new Middleware('permission:inventory_locations.update', only: ['edit', 'update']),
            new Middleware('permission:inventory_locations.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));

        $locations = InventoryLocation::query()
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('location_code', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($type !== '', static fn (Builder $query) => $query->where('type', $type))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/locations/index', [
            'locations' => $locations,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
            'locationTypes' => $this->locationTypeOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory/locations/create', [
            'locationTypes' => $this->locationTypeOptions(),
        ]);
    }

    public function store(StoreInventoryLocationRequest $request, CreateInventoryLocation $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('inventory-locations.index')->with('success', 'Inventory location created successfully.');
    }

    public function edit(InventoryLocation $inventoryLocation): Response
    {
        return Inertia::render('inventory/locations/edit', [
            'inventoryLocation' => $inventoryLocation,
            'locationTypes' => $this->locationTypeOptions(),
        ]);
    }

    public function update(UpdateInventoryLocationRequest $request, InventoryLocation $inventoryLocation, UpdateInventoryLocation $action): RedirectResponse
    {
        $action->handle($inventoryLocation, $request->validated());

        return to_route('inventory-locations.index')->with('success', 'Inventory location updated successfully.');
    }

    public function destroy(DeleteInventoryLocationRequest $request, InventoryLocation $inventoryLocation, DeleteInventoryLocation $action): RedirectResponse
    {
        $action->handle($inventoryLocation);

        return to_route('inventory-locations.index')->with('success', 'Inventory location deleted successfully.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function locationTypeOptions(): array
    {
        return collect(InventoryLocationType::cases())
            ->map(static fn (InventoryLocationType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->all();
    }
}
