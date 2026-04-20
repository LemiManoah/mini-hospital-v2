<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\OpenPharmacyPosCartAction;
use App\Enums\PharmacyPosCartStatus;
use App\Http\Requests\StorePharmacyPosCartRequest;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Support\BranchContext;
use App\Support\InventoryLocationAccess;
use App\Support\InventoryNavigationContext;
use App\Support\InventoryStockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyPosController implements HasMiddleware
{
    public function __construct(
        private InventoryLocationAccess $inventoryLocationAccess,
        private InventoryStockLedger $inventoryStockLedger,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.create', only: ['index', 'store']),
        ];
    }

    public function index(Request $request): Response
    {
        $branchId = BranchContext::getActiveBranchId();
        Auth::user();

        $locations = $this->dispensingLocations($branchId);

        $activeCart = is_string($branchId)
            ? PharmacyPosCart::query()
                ->with(['items.inventoryItem', 'inventoryLocation'])
                ->where('user_id', Auth::id())
                ->where('branch_id', $branchId)
                ->where('status', PharmacyPosCartStatus::Active)
                ->latest()
                ->first()
            : null;

        $heldCarts = is_string($branchId)
            ? PharmacyPosCart::query()
                ->where('user_id', Auth::id())
                ->where('branch_id', $branchId)
                ->where('status', PharmacyPosCartStatus::Held)
                ->latest('held_at')
                ->get()
                ->map(static fn (PharmacyPosCart $cart): array => [
                    'id' => $cart->id,
                    'cart_number' => $cart->cart_number,
                    'held_at' => $cart->held_at?->toISOString(),
                    'customer_name' => $cart->customer_name,
                ])
                ->values()
                ->all()
            : [];

        $stockBalances = is_string($branchId) && $activeCart !== null && is_string($activeCart->inventory_location_id)
            ? $this->itemBalancesForLocation($branchId, $activeCart->inventory_location_id)
            : collect();

        $searchableItems = $this->searchableItems($branchId, $activeCart?->inventory_location_id);

        return Inertia::render('pharmacy/pos/index', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'dispensingLocations' => $locations
                ->map(static fn (InventoryLocation $loc): array => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'location_code' => $loc->location_code,
                    'is_dispensing_point' => $loc->is_dispensing_point,
                ])
                ->values()
                ->all(),
            'activeCart' => $activeCart === null ? null : $this->serializeCart($activeCart, $stockBalances->all()),
            'heldCarts' => $heldCarts,
            'searchableItems' => $searchableItems,
            'defaults' => [
                'inventory_location_id' => $locations->first()?->id,
            ],
        ]);
    }

    public function store(
        StorePharmacyPosCartRequest $request,
        OpenPharmacyPosCartAction $action,
    ): RedirectResponse {
        $action->handle($request->validated());

        return to_route('pharmacy.pos.index')
            ->with('success', 'POS cart opened. Add items to begin.');
    }

    /**
     * @param  Collection<int, InventoryLocation>  $locations
     */
    private function dispensingLocations(?string $branchId): Collection
    {
        if (! is_string($branchId) || $branchId === '') {
            return collect();
        }

        $locations = $this->inventoryLocationAccess->accessibleLocations(
            Auth::user(),
            $branchId,
            ['pharmacy'],
        );

        $dispensingPoints = $locations
            ->filter(static fn (InventoryLocation $location): bool => $location->is_dispensing_point)
            ->values();

        return $dispensingPoints->isNotEmpty() ? $dispensingPoints : $locations->values();
    }

    /**
     * @return Collection<string, float>
     */
    private function itemBalancesForLocation(string $branchId, string $locationId): Collection
    {
        return $this->inventoryStockLedger
            ->summarizeByLocation($branchId)
            ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId)
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $rows): float => (float) $rows->sum('quantity'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchableItems(?string $branchId, ?string $locationId): array
    {
        if (! is_string($branchId) || ! is_string($locationId)) {
            return [];
        }

        $balances = $this->inventoryStockLedger
            ->summarizeByLocation($branchId)
            ->filter(static fn (array $balance): bool => $balance['inventory_location_id'] === $locationId
                && $balance['quantity'] > 0)
            ->groupBy('inventory_item_id')
            ->map(static fn (Collection $rows): float => (float) $rows->sum('quantity'));

        if ($balances->isEmpty()) {
            return [];
        }

        return InventoryItem::query()
            ->whereIn('id', $balances->keys())
            ->where('is_active', true)
            ->get()
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'generic_name' => $item->generic_name,
                'brand_name' => $item->brand_name,
                'strength' => $item->strength,
                'dosage_form' => $item->dosage_form?->value ?? $item->dosage_form,
                'unit_price' => round((float) ($item->default_selling_price ?? 0), 2),
                'available_quantity' => round($balances->get($item->id, 0.0), 3),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, float>  $stockBalances
     * @return array<string, mixed>
     */
    private function serializeCart(PharmacyPosCart $cart, array $stockBalances): array
    {
        return [
            'id' => $cart->id,
            'cart_number' => $cart->cart_number,
            'status' => $cart->status?->value,
            'customer_name' => $cart->customer_name,
            'customer_phone' => $cart->customer_phone,
            'notes' => $cart->notes,
            'inventory_location_id' => $cart->inventory_location_id,
            'inventory_location' => $cart->inventoryLocation === null ? null : [
                'id' => $cart->inventoryLocation->id,
                'name' => $cart->inventoryLocation->name,
                'location_code' => $cart->inventoryLocation->location_code,
            ],
            'items' => $cart->items
                ->map(fn (PharmacyPosCartItem $item): array => [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $item->inventoryItem?->name,
                    'generic_name' => $item->inventoryItem?->generic_name,
                    'brand_name' => $item->inventoryItem?->brand_name,
                    'strength' => $item->inventoryItem?->strength,
                    'dosage_form' => $item->inventoryItem?->dosage_form?->value ?? $item->inventoryItem?->dosage_form,
                    'quantity' => round((float) $item->quantity, 3),
                    'unit_price' => round((float) $item->unit_price, 2),
                    'discount_amount' => round((float) $item->discount_amount, 2),
                    'line_total' => $item->lineTotal(),
                    'available_quantity' => round($stockBalances[$item->inventory_item_id] ?? 0.0, 3),
                    'notes' => $item->notes,
                ])
                ->values()
                ->all(),
            'gross_amount' => round($cart->items->sum(fn (PharmacyPosCartItem $i): float => (float) $i->quantity * (float) $i->unit_price), 2),
            'discount_amount' => round($cart->items->sum(fn (PharmacyPosCartItem $i): float => (float) $i->discount_amount), 2),
            'total_amount' => round($cart->items->sum(fn (PharmacyPosCartItem $i): float => $i->lineTotal()), 2),
        ];
    }
}
