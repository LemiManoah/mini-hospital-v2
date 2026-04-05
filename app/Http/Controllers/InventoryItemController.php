<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInventoryItem;
use App\Actions\DeleteInventoryItem;
use App\Actions\UpdateInventoryItem;
use App\Enums\DrugCategory;
use App\Enums\DrugDosageForm;
use App\Enums\InventoryItemType;
use App\Http\Requests\DeleteInventoryItemRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InventoryItemController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:inventory_items.view', only: ['index', 'show']),
            new Middleware('permission:inventory_items.create', only: ['create', 'store']),
            new Middleware('permission:inventory_items.update', only: ['edit', 'update']),
            new Middleware('permission:inventory_items.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $search = mb_trim((string) $request->query('search', ''));
        $type = mb_trim((string) $request->query('type', ''));

        $items = InventoryItem::query()
            ->with(['unit:id,name,symbol'])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('generic_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('brand_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('manufacturer', 'like', sprintf('%%%s%%', $search));
                });
            })
            ->when($type !== '', static fn (Builder $query) => $query->where('item_type', $type))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('inventory/items/index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
            'itemTypes' => $this->itemTypeOptions(),
        ]);
    }

    public function show(InventoryItem $inventoryItem): Response
    {
        return Inertia::render('inventory/items/show', [
            'inventoryItem' => $inventoryItem->load([
                'unit:id,name,symbol',
                'batches' => fn ($query) => $query->with('inventoryLocation:id,name')->orderBy('expiry_date'),
                'stockMovements' => fn ($query) => $query->with(['inventoryLocation:id,name', 'user:id,name'])->latest()->limit(50),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory/items/create', $this->formOptions());
    }

    public function store(StoreInventoryItemRequest $request, CreateInventoryItem $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('inventory-items.index')->with('success', 'Inventory item created successfully.');
    }

    public function edit(InventoryItem $inventoryItem): Response
    {
        return Inertia::render('inventory/items/edit', [
            'inventoryItem' => $inventoryItem->loadMissing(['unit:id,name,symbol']),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem, UpdateInventoryItem $action): RedirectResponse
    {
        $action->handle($inventoryItem, $request->validated());

        return to_route('inventory-items.index')->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(DeleteInventoryItemRequest $request, InventoryItem $inventoryItem, DeleteInventoryItem $action): RedirectResponse
    {
        $action->handle($inventoryItem);

        return to_route('inventory-items.index')->with('success', 'Inventory item deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'itemTypes' => $this->itemTypeOptions(),
            'unitOptions' => Unit::query()
                ->orderBy('name')
                ->get(['id', 'name', 'symbol'])
                ->map(static fn (Unit $unit): array => [
                    'value' => $unit->id,
                    'label' => sprintf('%s (%s)', $unit->name, $unit->symbol),
                ])
                ->all(),
            'drugCategories' => collect(DrugCategory::cases())
                ->map(static fn (DrugCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ])
                ->all(),
            'dosageForms' => collect(DrugDosageForm::cases())
                ->map(static fn (DrugDosageForm $form): array => [
                    'value' => $form->value,
                    'label' => $form->label(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function itemTypeOptions(): array
    {
        return collect(InventoryItemType::cases())
            ->map(static fn (InventoryItemType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->all();
    }
}
