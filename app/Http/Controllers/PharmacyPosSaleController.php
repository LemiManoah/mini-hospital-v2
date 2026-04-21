<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\FinalizePharmacyPosSaleAction;
use App\Enums\PharmacyPosCartStatus;
use App\Enums\PharmacyPosSaleStatus;
use App\Http\Requests\FinalizePharmacyPosSaleRequest;
use App\Models\PharmacyPosCart;
use App\Models\PharmacyPosCartItem;
use App\Models\PharmacyPosPayment;
use App\Models\PharmacyPosSale;
use App\Models\PharmacyPosSaleItem;
use App\Support\BranchContext;
use App\Support\InventoryNavigationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PharmacyPosSaleController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_pos.complete', only: ['store']),
            new Middleware('permission:pharmacy_pos.view', only: ['show', 'checkout']),
            new Middleware('permission:pharmacy_pos.view_history', only: ['index']),
        ];
    }

    public function index(Request $request): Response
    {
        $branchId = BranchContext::getActiveBranchId();
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        $query = PharmacyPosSale::query()
            ->where('branch_id', $branchId)
            ->with(['inventoryLocation', 'createdBy.staff'])
            ->latest('sold_at');

        if ($search !== '') {
            $query->where(static function (Builder $q) use ($search): void {
                $q->where('sale_number', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('customer_name', 'like', sprintf('%%%s%%', $search));
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($from !== '') {
            $query->whereDate('sold_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('sold_at', '<=', $to);
        }

        $sales = $query->paginate(25)->withQueryString();

        return Inertia::render('pharmacy/pos/history', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'sales' => $sales->through(fn (PharmacyPosSale $sale): array => $this->serializeHistorySale($sale)),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from' => $from,
                'to' => $to,
            ],
            'statuses' => array_map(
                static fn (PharmacyPosSaleStatus $s): array => ['value' => $s->value, 'label' => $s->label()],
                PharmacyPosSaleStatus::cases(),
            ),
        ]);
    }

    public function checkout(Request $request, PharmacyPosCart $cart): Response
    {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 404);

        $cart->load(['items.inventoryItem', 'inventoryLocation']);

        $grossAmount = round($cart->items->reduce(
            static fn (float $carry, PharmacyPosCartItem $item): float => $carry + round((float) $item->quantity * (float) $item->unit_price, 2),
            0.0,
        ), 2);
        $discountAmount = round($cart->items->reduce(
            static fn (float $carry, PharmacyPosCartItem $item): float => $carry + (float) $item->discount_amount,
            0.0,
        ), 2);
        $totalAmount = max(0.0, round($grossAmount - $discountAmount, 2));

        return Inertia::render('pharmacy/pos/checkout', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'cart' => [
                'id' => $cart->id,
                'cart_number' => $cart->cart_number,
                'customer_name' => $cart->customer_name,
                'customer_phone' => $cart->customer_phone,
                'inventory_location' => $cart->inventoryLocation === null ? null : [
                    'id' => $cart->inventoryLocation->id,
                    'name' => $cart->inventoryLocation->name,
                ],
                'items' => $cart->items->map(fn (PharmacyPosCartItem $item): array => $this->serializeCheckoutCartItem($item))->values()->all(),
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ],
        ]);
    }

    public function store(
        FinalizePharmacyPosSaleRequest $request,
        PharmacyPosCart $cart,
        FinalizePharmacyPosSaleAction $action,
    ): RedirectResponse {
        abort_unless($cart->branch_id === BranchContext::getActiveBranchId(), 404);
        abort_unless($cart->status === PharmacyPosCartStatus::Active, 403, 'This cart is no longer active.');

        /** @var array{paid_amount?: int|float|string|null, payment_method?: string|null, reference_number?: string|null, notes?: string|null} $paymentData */
        $paymentData = $request->validated();

        $sale = $action->handle($cart, $paymentData);

        return to_route('pharmacy.pos.sales.show', ['sale' => $sale])
            ->with('success', 'Sale completed. Receipt is ready.');
    }

    public function show(Request $request, PharmacyPosSale $sale): Response
    {
        abort_unless($sale->branch_id === BranchContext::getActiveBranchId(), 404);

        $sale->load(['items.inventoryItem', 'items.allocations.inventoryBatch', 'payments', 'inventoryLocation', 'createdBy.staff']);

        $authUser = Auth::user();

        return Inertia::render('pharmacy/pos/sales/show', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'can' => [
                'void' => $authUser?->can('pharmacy_pos.void') ?? false,
                'refund' => $authUser?->can('pharmacy_pos.refund') ?? false,
            ],
            'sale' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'sale_type' => $sale->sale_type,
                'status' => $sale->status->value,
                'status_label' => $sale->status->label(),
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'gross_amount' => (float) $sale->gross_amount,
                'discount_amount' => (float) $sale->discount_amount,
                'paid_amount' => (float) $sale->paid_amount,
                'balance_amount' => (float) $sale->balance_amount,
                'change_amount' => (float) $sale->change_amount,
                'sold_at' => $sale->sold_at?->toISOString(),
                'notes' => $sale->notes,
                'inventory_location' => $sale->inventoryLocation === null ? null : [
                    'id' => $sale->inventoryLocation->id,
                    'name' => $sale->inventoryLocation->name,
                    'location_code' => $sale->inventoryLocation->location_code,
                ],
                'sold_by' => $this->saleUserLabel($sale),
                'items' => $sale->items->map(fn (PharmacyPosSaleItem $item): array => $this->serializeSaleItem($item))->values()->all(),
                'payments' => $sale->payments->map(fn (PharmacyPosPayment $payment): array => $this->serializePayment($payment))->values()->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHistorySale(PharmacyPosSale $sale): array
    {
        return [
            'id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'status' => $sale->status->value,
            'status_label' => $sale->status->label(),
            'customer_name' => $sale->customer_name,
            'gross_amount' => (float) $sale->gross_amount,
            'discount_amount' => (float) $sale->discount_amount,
            'paid_amount' => (float) $sale->paid_amount,
            'balance_amount' => (float) $sale->balance_amount,
            'sold_at' => $sale->sold_at?->toISOString(),
            'location_name' => $sale->inventoryLocation?->name,
            'sold_by' => $this->saleUserLabel($sale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCheckoutCartItem(PharmacyPosCartItem $item): array
    {
        return [
            'id' => $item->id,
            'item_name' => $item->inventoryItem?->name,
            'generic_name' => $item->inventoryItem?->generic_name,
            'quantity' => round((float) $item->quantity, 3),
            'unit_price' => round((float) $item->unit_price, 2),
            'discount_amount' => round((float) $item->discount_amount, 2),
            'line_total' => max(0.0, round(
                round((float) $item->quantity * (float) $item->unit_price, 2) - (float) $item->discount_amount,
                2
            )),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSaleItem(PharmacyPosSaleItem $item): array
    {
        return [
            'id' => $item->id,
            'item_name' => $item->inventoryItem?->name,
            'generic_name' => $item->inventoryItem?->generic_name,
            'quantity' => round((float) $item->quantity, 3),
            'unit_price' => round((float) $item->unit_price, 2),
            'discount_amount' => round((float) $item->discount_amount, 2),
            'line_total' => round((float) $item->line_total, 2),
            'notes' => $item->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(PharmacyPosPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => round((float) $payment->amount, 2),
            'payment_method' => $payment->payment_method,
            'reference_number' => $payment->reference_number,
            'payment_date' => $payment->payment_date->toISOString(),
            'is_refund' => $payment->is_refund,
            'notes' => $payment->notes,
        ];
    }

    private function saleUserLabel(PharmacyPosSale $sale): ?string
    {
        $createdBy = $sale->createdBy;
        $staff = $createdBy?->staff;

        return $staff === null
            ? $createdBy?->email
            : mb_trim(sprintf('%s %s', $staff->first_name, $staff->last_name));
    }
}
